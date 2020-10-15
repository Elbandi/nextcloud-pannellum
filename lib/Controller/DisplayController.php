<?php

declare(strict_types=1);

/**
 * @copyright 2014 Lukas Reschke lukas@owncloud.com
 *
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Pannellum\Controller;

use OCA\Pannellum\AppInfo\Application;
use OCA\Pannellum\Service\IXmpDataReader;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\NotFoundResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\IManager;

class DisplayController extends Controller {

	/** @var IRootFolder */
	private $rootFolder;

	/** @var IManager */
	private $shareManager;

	/** @var string|null */
	private $userId;

	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var IXmpDataReader */
	private $xmpDataReader;

	/**
	 * @param IRequest $request
	 * @param IURLGenerator $urlGenerator
	 */
	public function __construct(IRequest $request, IRootFolder $rootFolder, IManager $shareManager, IURLGenerator $urlGenerator, IXmpDataReader $xmpDataReader, $userId) {
		parent::__construct(Application::APP_ID, $request);
		$this->rootFolder = $rootFolder;
		$this->shareManager = $shareManager;
		$this->userId = $userId;
		$this->urlGenerator = $urlGenerator;
		$this->xmpDataReader = $xmpDataReader;
	}

	protected function getTemplateResponse($file, $sharingToken, $fileName, bool $autoload) {
		// TODO: move to caller
		if (is_null($sharingToken)) {
			// TODO: find better url generator
			$fileUrl = \OCP\Util::linkToRemote('dav/files/' . $this->userId) . substr($file->getPath(), strlen('/' . $this->userId . '/files/'));
		} else {
			if (!strncmp($fileName, '/', 1)) {
				$fileUrl = $this->urlGenerator->linkToRouteAbsolute('files_sharing.sharecontroller.downloadShare', ['token' => $sharingToken, 'path' => dirname($fileName), 'files' => basename($fileName)]);
			} else {
				$fileUrl = $this->urlGenerator->linkToRouteAbsolute('files_sharing.sharecontroller.downloadShare', ['token' => $sharingToken]);
			}
		}
		// $fileName = urldecode($fileName)
		// FIXME: local only
		$configFromURL = false;
		$xmp = $this->xmpDataReader->getXmpTag($file->getStorage()->getLocalFile($file->getInternalPath()));
		if (isset($xmp["MultiResUrl"])) {
			$fileUrl = $xmp["MultiResUrl"];
			$configFromURL = true;
		}
		$params = [
			'urlGenerator' => $this->urlGenerator,
			'configFromURL' => $configFromURL,
			'fileName' => $fileUrl,
			'origName' => $fileName,
			'autoload' => $autoload ? 'true' : 'false',
		];
		$response = new TemplateResponse(Application::APP_ID, 'viewer', $params, 'blank');

		$response->getContentSecurityPolicy()->addAllowedConnectDomain('*');

		return $response;
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @param string $shareToken the token of the public share
	 * @param string $fileName the filename (if we're dealing with a directory share)
	 * @return Response
	 */
	public function show(string $sharingToken, string $fileName): Response {
		try {
			$fileNode = $this->getShareFile($sharingToken);
		} catch (\Exception $e) {
			return new NotFoundResponse($e->getMessage());
		}
		return $this->getTemplateResponse($fileNode, $sharingToken, $fileName, true);
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @param string $fileName the filename (if we're dealing with a directory share)
	 * @param int $fileId The fileId of the file from which the xmp-data shall be loaded
	 * @param string $shareToken the token of the public share
	 * @return TemplateResponse
	 */
	public function load(string $fileName, int $fileId, $sharingToken): Response {
		try {
			if (!is_null($sharingToken)) {
				$fileNode = $this->getShareFile($sharingToken, $fileId);
			} else if (!is_null($this->userId)) {
				$fileNode = $this->getUserFile($fileId);
			} else {
				throw new \Exception('Share not found');
			}
		} catch (\Exception $e) {
			return new NotFoundResponse($e->getMessage());
		}
		return $this->getTemplateResponse($fileNode, $sharingToken, $fileName, false);
	}

	private function getUserFile($fileId) {
		$userFolder = $this->rootFolder->getUserFolder($this->userId);
		$arrFiles = $userFolder->getById($fileId);
		if (!isset($arrFiles[0])) {
			throw new \Exception('Could not locate node linked to ID: ' . $fileId);
		}
		return $arrFiles[0];
	}

	private function getShareFile($sharingToken, $fileId = null) {
		try {
			$share = $this->shareManager->getShareByToken($sharingToken);
		} catch (ShareNotFound $e) {
			throw new \Exception('Share not found');
		}
		if (!$share->getNode()->isReadable() || !$share->getNode()->isShareable()) {
			throw new \Exception('Share not found');
		}
		$shareNode = $share->getNode();
		if ($shareNode instanceof \OCP\Files\File) {
			return $shareNode;
		}
		$arrFiles = $shareNode->getById($fileId);
		if (!isset($arrFiles[0])) {
			throw new \Exception('Could not locate node linked to ID: ' . $fileId);
		}
		return $arrFiles[0];
	}
}
