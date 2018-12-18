describe('Social app setup', function() {

	before(function() {
		cy.clearCookie('nc_session_id')
		Cypress.Cookies.defaults({
			whitelist: [ 'nc_session_id', 'nc_username', 'nc_token', 'oc_sessionPassphrase' ]
		})
		cy.login('admin12', 'admin12')
	})

	it('See the welcome message', function() {
		cy.visit('/apps/social/')
		cy.get('.social__welcome').should('contain', 'Nextcloud becomes part of the federated social networks!')
		cy.get('.social__welcome').find('.icon-close').click()
		cy.get('.social__welcome').should('not.exist')
	})

	it('See the .well-known setup error', function() {
		cy.window().then((win) => {
			if (win.oc_isadmin) {
				cy.get('.setup').should('contain', '.well-known/webfinger isn\'t properly set up!')
			}
		})
	})

	it('See the empty content illustration', function() {
		//cy.get('#app-navigation').contains('Home').click()
		//cy.get('.emptycontent').should('be.visible')
		cy.get('#app-navigation').contains('Direct messages').click()
		cy.get('.emptycontent').should('be.visible').contains('No direct messages found')
		cy.get('#app-navigation').contains('Profile').click()
		cy.get('.emptycontent').should('be.visible').contains('No posts found')
	})

})
