<!--
 - @copyright Copyright (c) 2020 Andras Elso <elos.andras@gmail.com>
 -
 - @author Andras Elso <elos.andras@gmail.com>
 -
 - @license GNU AGPL version 3 or any later version
 -
 - This program is free software: you can redistribute it and/or modify
 - it under the terms of the GNU Affero General Public License as
 - published by the Free Software Foundation, either version 3 of the
 - License, or (at your option) any later version.
 -
 - This program is distributed in the hope that it will be useful,
 - but WITHOUT ANY WARRANTY; without even the implied warranty of
 - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 - GNU Affero General Public License for more details.
 -
 - You should have received a copy of the GNU Affero General Public License
 - along with this program. If not, see <http://www.gnu.org/licenses/>.
 -
 -->

<template>
	<iframe
		id="pannellumframe"
		ref="pannellumframe"
		allowfullscreen
		style="border-style:none;"
		:src="iframeSrc"
		@load="onFrameLoad" />
</template>

<script>
import { generateUrl } from '@nextcloud/router'

export default {
	name: 'Pannellum',
	props: {
		filename: {
			type: String,
			default: null,
		},
		fileid: {
			type: Number,
			default: null,
		},
		hasPreview: {
			type: Boolean,
			required: false,
			default: () => false,
		},
	},
	data() {
		return {
			viewer: null,
			active: false,
		}
	},
	computed: {
		isPublic() {
			return !!(document.getElementById('isPublic'))
		},
		shareToken() {
			return document.getElementById('sharingToken') ? document.getElementById('sharingToken').value : null
		},
		iframeSrc() {
			if (this.isPublic) {
				return generateUrl('/apps/pannellum/load?fileName={fileName}&fileId={fileId}&sharingToken={sharingToken}', {
					fileName: this.filename,
					fileId: this.fileid,
					sharingToken: this.shareToken,
				})
			} else {
				return generateUrl('/apps/pannellum/load?fileName={fileName}&fileId={fileId}', {
					fileName: this.filename,
					fileId: this.fileid,
				})
			}
		},
	},
	watch: {
		active(val, old) {
			// the item was hidden before and is now the current view
			if (val === true && old === false) {
				this.active = true
			} else {
				this.active = false
			}
		},
	},
	async mounted() {
		this.doneLoading()
		this.$nextTick(function() {
			this.$el.focus()
		})
	},
	methods: {
		onFrameLoad() {
			if (this.active) {
				document.getElementById('pannellumframe').contentWindow.load()
			}
		},
	},
}
</script>

<style scoped lang="scss">
	#pannellumframe {
		width: 100vw;
		height: 90vh;
		align-self: center;
		justify-self: center;
		margin: 0;
		padding: 0;
	}
</style>
