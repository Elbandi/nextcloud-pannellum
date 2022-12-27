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

namespace OCA\Pannellum\Listener;

use OCA\Pannellum\AppInfo\Application;
use OCA\Viewer\Event\LoadViewer;
use OCA\Files\Event\LoadAdditionalScriptsEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Util;

class LoadPannellumScript implements IEventListener {
	public function handle(Event $event): void {
		if (!($event instanceof LoadViewer || $event instanceof LoadAdditionalScriptsEvent)) {
			return;
		}

		Util::addScript(Application::APP_ID, 'pannellum-main', 'viewer');
	}
}
