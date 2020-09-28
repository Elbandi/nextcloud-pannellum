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

namespace OCA\Pannellum\Migration;

use OCP\Files\IMimeTypeLoader;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class AddMimetypeToFilecache implements IRepairStep {

	private $mimeTypeLoader;

	public function __construct(IMimeTypeLoader $mimeTypeLoader) {
		$this->mimeTypeLoader = $mimeTypeLoader;
	}

	public function getName() {
		return 'Add custom mimetype to filecache';
	}

	public function run(IOutput $output) {
		// And update the filecache for it.
		$mimes = [
			'3dpng' => 'image/x-3d-png',
			'3djpg' => 'image/x-3d-jpg',
		];
		foreach($mimes as $ext => $mime) {
			$mimetypeId = $this->mimeTypeLoader->getId($mime);
			$this->mimeTypeLoader->updateFilecache($ext, $mimetypeId);
			$output->info("Added custom $ext => $mime mimetype to filecache.");
		}
	}
}
