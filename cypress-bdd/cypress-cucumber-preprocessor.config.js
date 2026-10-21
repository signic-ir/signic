// Configuration for @badeball/cypress-cucumber-preprocessor.
// Loaded via cosmiconfig from the Cypress project root.
module.exports = {
  stepDefinitions: [
    'features/[filepath].js',
  ],
  json: {
    enabled: true,
    output: 'cypress/reports/cucumber-report.json',
  },
  messages: {
    enabled: true,
    output: 'cypress/reports/cucumber-messages.ndjson',
  },
}
