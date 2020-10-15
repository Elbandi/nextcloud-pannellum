/**
 * @copyright Copyright (c) 2020 John Molakvoæ <skjnldsv@protonmail.com>
 *
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

import { generateUrl } from '@nextcloud/router'

window.addEventListener('DOMContentLoaded', function() {
	const isPublic = document.getElementById('isPublic') ? document.getElementById('isPublic').value === '1' : false
	const mimetype = document.getElementById('mimetype') ? document.getElementById('mimetype').value : undefined
	const hideDownload = document.getElementById('hideDownload') ? document.getElementById('hideDownload').value === 'true' : false
	if (isPublic && mimetype.startsWith('image/x-3d') && !hideDownload) {
		console.debug('Pannellum initialized for public page', {
			isPublicPage: isPublic,
			hideDownload,
		})

		const contentElmt = document.getElementById('files-public-content')
		const sharingTokenElmt = document.getElementById('sharingToken')

		const sharingToken = sharingTokenElmt.value
		const fileName = document.getElementById('filename').value
		const viewerUrl = generateUrl('/apps/pannellum/show?fileName={fileName}&sharingToken={sharingToken}', { fileName, sharingToken })

		// Create viewer frame
		const viewerNode = document.createElement('iframe')
		viewerNode.src = viewerUrl
		viewerNode.style.height = '100%'
		viewerNode.style.width = '100%'
		viewerNode.style.position = 'absolute'

		// Inject viewer
		if (contentElmt) {
			contentElmt.innerHTML = ''
			contentElmt.appendChild(viewerNode)
			// footerElmt.style.display = 'none'
		} else {
			console.error('Unable to inject the Pannellum Viewer')
		}
	}
})
