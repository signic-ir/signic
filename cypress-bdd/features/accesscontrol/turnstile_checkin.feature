Feature: Turnstile Access Control
  As an event staff member, I want to manage attendee check-in and check-out via turnstiles so that I can control event access securely.

  Acceptance Criteria:
  - Check-in attendee via turnstile
  - Check-out attendee via turnstile
  - Prevent check-out without check-in
  - Prevent duplicate check-in
  - Race condition prevention
  - Invalid QR token at turnstile

  Background:
    Given the system is running
    And I have access to the AccessControl module
    And there is an active turnstile at entrance "Main Gate"
    And there is a confirmed attendee with a valid QR badge

  Scenario: Check-in attendee via turnstile
    Given I am at turnstile "Main Gate"
    And I scan a valid QR badge for attendee John Doe
    When I attempt to check-in the attendee
    Then the check-in should succeed
    And the attendee status should be updated to "CHECKED_IN"
    And a scan event should be recorded with type "CHECK_IN"
    And the operation should be idempotent (scanning again should not create duplicate events)

  Scenario: Check-out attendee via turnstile
    Given an attendee John Doe is currently checked-in
    And I am at turnstile "Main Gate"
    And I scan the valid QR badge for attendee John Doe
    When I attempt to check-out the attendee
    Then the check-out should succeed
    And the attendee status should be updated to "CHECKED_OUT"
    And a scan event should be recorded with type "CHECK_OUT"

  Scenario: Prevent check-out without check-in
    Given an attendee John Doe has never been checked-in
    And I am at turnstile "Main Gate"
    And I scan the valid QR badge for attendee John Doe
    When I attempt to check-out the attendee
    Then the check-out should fail
    And I should receive an error about invalid check-out attempt
    And no scan event should be recorded

  Scenario: Prevent duplicate check-in
    Given an attendee John Doe is already checked-in
    And I am at turnstile "Main Gate"
    And I scan the valid QR badge for attendee John Doe
    When I attempt to check-in the attendee again
    Then the check-in should fail
    And I should receive an error about already being checked-in
    And no new scan event should be recorded

  Scenario: Race condition prevention
    Given multiple staff members attempt to check-in the same attendee simultaneously
    When they scan the QR badge at the same time
    Then only one check-in should succeed
    And the others should receive appropriate errors
    And exactly one scan event should be recorded

  Scenario: Invalid QR token at turnstile
    Given I am at turnstile "Main Gate"
    And I scan an invalid QR token
    When I attempt to process the scan
    Then the operation should fail
    And I should receive an error about invalid QR token
    And no scan event should be recorded