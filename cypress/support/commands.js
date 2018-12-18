// ***********************************************
// This example commands.js shows you how to
// create various custom commands and overwrite
// existing commands.
//
// For more comprehensive examples of custom
// commands please read more here:
// https://on.cypress.io/custom-commands
// ***********************************************
//
//
// -- This is a parent command --
Cypress.Commands.add("login", (user, password) => {
	cy.visit('/login')
	cy.get('input[name=user]').type(user)
	cy.get('input[name=password]').type(password)
	cy.get('input#submit').click()

	// we should be redirected to /dashboard
	cy.url().should('include', '/apps/files')

	// and our cookie should be set to 'cypress-session-cookie'

})

Cypress.Commands.add('axe', () => {
	// Inspired by https://marmelab.com/blog/2018/07/18/accessibility-performance-testing-puppeteer.html
	const defaultOptions = {
		violationsTreshold: 0,
		incompleteTreshold: 0,
	};
	const printInvalidNode = node =>
		`- ${node.html}\n\t${
			node.any.map(check => check.message).join('\n\t')
		}`;

	const printInvalidRule = rule =>
		`${rule.help} on ${
			rule.nodes.length
		} nodes\r\n${rule.nodes
				.map(printInvalidNode)
				.join('\n')}`;


	const hasNoAccessibilityIssues = (accessibilityReport, options) => {
		let violations = [];
		let incomplete = [];
		const finalOptions = Object.assign({}, defaultOptions, options);
		if (
			accessibilityReport.violations.length >
			finalOptions.violationsTreshold
		) {
			violations = [
				`Expected to have no more than ${
					finalOptions.violationsTreshold
				} violations. Detected ${
					accessibilityReport.violations.length
				} violations:\n`,
			].concat(accessibilityReport.violations.map(printInvalidRule));
		}

		if (
			finalOptions.incompleteTreshold !== false &&
			accessibilityReport.incomplete.length >
			finalOptions.incompleteTreshold
		) {
			incomplete = [
				`Expected to have no more than ${
					finalOptions.incompleteTreshold
				} incomplete. Detected ${
					accessibilityReport.incomplete.length
				} incomplete:\n`,
			].concat(accessibilityReport.incomplete.map(printInvalidRule));
		}

		const message = [].concat(violations, incomplete).join('\n');
		const pass =
			accessibilityReport.violations.length <=
			finalOptions.violationsTreshold &&
			(finalOptions.incompleteTreshold === false ||
				accessibilityReport.incomplete.length <=
				finalOptions.incompleteTreshold);

		return {
			pass,
			message: message,
		};

	}
	cy.window().then((win) => {
		/*var axe = require('axe-core')
		console.log(win.document)
		return axe.run(win.document).then(function(results) {
			const issues = hasNoAccessibilityIssues(results, {})
			assert.isTrue(issues.pass, issues.message);
			if (!issues.pass) {
				cy.log(issues.message)
			}
		})*/
	})
})
//
//
// -- This is a child command --
// Cypress.Commands.add("drag", { prevSubject: 'element'}, (subject, options) => { ... })
//
//
// -- This is a dual command --
// Cypress.Commands.add("dismiss", { prevSubject: 'optional'}, (subject, options) => { ... })
//
//
// -- This is will overwrite an existing command --
// Cypress.Commands.overwrite("visit", (originalFn, url, options) => { ... })
