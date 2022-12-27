<?php
declare(strict_types=1);
/**
 * @copyright Copyright (c) 2020 Andras Elso <elos.andras@gmail.com>
 *
 * @author Andras Elso <elos.andras@gmail.com>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Pannellum\AppInfo;

use OCA\Pannellum\Listener\LoadPannellumScript;
use OCA\Pannellum\Preview\PanoPreviewIProviderV2;
use OCA\Pannellum\Service\IXmpDataReader;
use OCA\Pannellum\Service\XmpDataReader;

use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCA\Files\Event\LoadAdditionalScriptsEvent;
use OCA\Viewer\Event\LoadViewer;

use OCP\AppFramework\App;
use OCP\Util;

class Application extends App implements IBootstrap {

	const APP_ID = 'pannellum';

	public function __construct(array $params = []) {
		parent::__construct(self::APP_ID, $params);
	}

	public function register(IRegistrationContext $context): void {
		$container = $this->getContainer();
		$server = $container->getServer();

		/** @var IMimeTypeDetector $mimeTypeDetector */
		$mimeTypeDetector = $server->getMimeTypeDetector();

		/** @var IEventDispatcher $eventDispatcher */
		$eventDispatcher = $server->getEventDispatcher();

		// registerType without getAllMappings will prevent loading nextcloud's default mappings.
		$mimeTypeDetector->getAllMappings();
		$mimeTypeDetector->registerType('3dpng', 'image/x-3d-png', null);
		$mimeTypeDetector->registerType('3djpg', 'image/x-3d-jpg', null);

		// Watch Viewer load event
		$context->registerEventListener(LoadViewer::class, LoadPannellumScript::class);
//		$eventDispatcher->addServiceListener(LoadAdditionalScriptsEvent::class, LoadPannellumScript::class);
		$eventDispatcher->addListener('OCA\Files_Sharing::loadAdditionalScripts', function () {
			Util::addScript(self::APP_ID, 'pannellum-public');
		});

		$context->registerServiceAlias(IXmpDataReader::class, XmpDataReader::class);
#		$context->registerEventListener(AddContentSecurityPolicyEvent::class, CSPListener::class);
		$context->registerPreviewProvider(PanoPreviewIProviderV2::class, '/^image\/x-3d/');
	}

	public function boot(IBootContext $context): void {

	}
}
