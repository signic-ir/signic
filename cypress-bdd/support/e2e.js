// cypress-bdd/support/e2e.js
//
// Support file for the Cypress BDD suite.
//
// NOTE: v28's step-definition registry is created PER SPEC by the
// preprocessor template, so steps and `Before` hooks CANNOT be declared here
// — they live in each `features/<module>/<feature>.js` file, which is loaded
// inside the registry scope (see `stepDefinitions: ['features/[filepath].js']`).
//
// Steps track their own most recent response on the shared World object
// (`world.lastResponse`) rather than via an `@lastResponse` alias — because
// `cy.request` already returns the response as the chain value, a simple
// assignment is enough and avoids the "Cypress command inside promise"
// rejection that an alias would produce here.
