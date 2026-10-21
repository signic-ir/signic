Feature: Attendee Registration
  As an event organizer, I want to register attendees for events so that I can manage event participation.

  Acceptance Criteria:
  - Register attendee for event with valid details
  - Handle duplicate attendee registration (same email for same event)
  - Reject invalid ticket types
  - Generate QR token for attendee
  - Validate QR token (valid and invalid)
  - Handle expired QR token

  Background:
    Given the system is running
    And I have access to the Registration module
    And there is an active event "Tech Conference 2024"

  Scenario: Register attendee for event
    Given I am an authenticated event organizer
    When I register an attendee with the following details:
      | first_name | last_name | email               | phone          | ticket_type   |
      | John       | Doe       | john@example.com    | +989123456789  | GENERAL       |
    And I associate the attendee with event "Tech Conference 2024"
    Then the attendee should be created successfully
    And the attendee status should be "CONFIRMED"
    And a QR badge should be generated for the attendee
    And a print job should be queued for the attendee's badge

  Scenario: Register duplicate attendee
    Given I am an authenticated event organizer
    And an attendee with email "john@example.com" already exists for event "Tech Conference 2024"
    When I try to register another attendee with email "john@example.com" for the same event
    Then I should receive a validation error about duplicate email
    And no new attendee should be created

  Scenario: Register attendee with invalid ticket type
    Given I am an authenticated event organizer
    When I try to register an attendee with an invalid ticket type "INVALID"
    Then I should receive a validation error about invalid ticket type
    And no attendee should be created

  Scenario: Generate QR token for attendee
    Given an attendee exists with ID 1
    When I generate a QR token for the attendee
    Then I should receive a token in the format "attendee_id|random_string|signature"
    And the token should be cryptographically secure
    And the token should be unique for this attendee

  Scenario: Validate QR token
    Given a valid QR token "1|abc123|signature" exists for attendee ID 1
    When I validate the QR token "1|abc123|signature"
    Then I should get the attendee ID 1 as validation result
    And the token should be marked as validated

  Scenario: Validate invalid QR token
    Given I have an invalid QR token "1|abc123|invalid_signature"
    When I validate the invalid QR token
    Then I should get a validation error
    And no attendee ID should be returned

  Scenario: Expired QR token
    Given a QR token was generated more than 24 hours ago
    When I try to validate the expired QR token
    Then I should get an expiration error
    And no attendee ID should be returned