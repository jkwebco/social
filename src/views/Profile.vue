<!--
  - @copyright Copyright (c) 2018 Julius Härtl <jus@bitgrid.net>
  -
  - @author Julius Härtl <jus@bitgrid.net>
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
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program. If not, see <http://www.gnu.org/licenses/>.
  -
  -->

<template>
	<div :class="{'icon-loading': !accountLoaded}" class="social__wrapper">
		<profile-info v-if="accountLoaded && accountInfo" :uid="uid" />
		<router-view v-if="accountLoaded && accountInfo" name="details" />
		<empty-content v-if="accountLoaded && !accountInfo" :item="emptyContentData" />
	</div>
</template>

<style scoped>

	.social__wrapper.icon-loading {
		margin-top: 50vh;
	}

</style>

<script>
import {
	PopoverMenu,
	AppNavigation,
	Multiselect,
	Avatar
} from 'nextcloud-vue'
import TimelineEntry from './../components/TimelineEntry'
import ProfileInfo from './../components/ProfileInfo'
import EmptyContent from '../components/EmptyContent'

export default {
	name: 'Profile',
	components: {
		EmptyContent,
		PopoverMenu,
		AppNavigation,
		TimelineEntry,
		Multiselect,
		Avatar,
		ProfileInfo
	},
	data: function() {
		return {
			state: [],
			uid: null
		}
	},
	computed: {
		serverData: function() {
			return this.$store.getters.getServerData
		},
		currentUser: function() {
			return OC.getCurrentUser()
		},
		socialId: function() {
			return '@' + OC.getCurrentUser().uid + '@' + OC.getHost()
		},
		timeline: function() {
			return this.$store.getters.getTimeline
		},
		accountInfo: function() {
			return this.$store.getters.getAccount(this.uid)
		},
		accountLoaded() {
			return this.$store.getters.accountLoaded(this.uid)
		},
		emptyContentData() {
			return {
				image: 'img/undraw/profile.svg',
				title: t('social', 'User not found'),
				description: t('social', 'Sorry, we could not find the account of {userId}', { userId: this.uid })
			}
		}
	},
	beforeMount() {
		this.uid = this.$route.params.account
		this.$store.dispatch('fetchAccountInfo', this.uid)
	},
	methods: {
	}
}
</script>
