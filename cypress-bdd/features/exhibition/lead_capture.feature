Feature: Exhibitor Lead Capture
  As an exhibitor representative, I want to capture and manage leads from event attendees so that I can follow up on potential business opportunities.

  Acceptance Criteria:
  - Create lead from attendee interaction
  - Update lead status
  - Add tag to lead
  - Remove tag from lead
  - Lead permission validation
  - Lead validation - required fields
  - Create lead without notes

  Background:
    Given the system is running
    And I have access to the Exhibition module
    And there is an active event "Tech Conference 2024"
    And there is an exhibitor "Acme Corp" with a booth at the event
    And there is an attendee "John Doe" registered for the event

  Scenario: Create lead from attendee interaction
    Given I am an authenticated exhibitor representative for "Acme Corp"
    When I capture a lead for attendee "John Doe" with the following details:
      | interest_level | notes                          |
      | HIGH           | Interested in enterprise license |
    And I associate the lead with booth "Acme Corp Booth"
    Then the lead should be created successfully
    And the lead should be associated with the correct attendee
    And the lead should have the correct interest level
    And the lead should have the correct notes
    And the lead status should be "NEW"

  Scenario: Update lead status
    Given a lead exists for attendee "John Doe" from exhibitor "Acme Corp"
    And the lead status is "NEW"
    When I update the lead status to "QUALIFIED"
    Then the lead status should be updated successfully
    And the update should be recorded in the lead's history

  Scenario: Add tag to lead
    Given a lead exists for attendee "John Doe" from exhibitor "Acme Corp"
    When I add the tag "enterprise" to the lead
    Then the tag should be associated with the lead
    And the lead should be filterable by the "enterprise" tag

  Scenario: Remove tag from lead
    Given a lead exists for attendee "John Doe" from exhibitor "Acme Corp"
    And the lead has the tag "enterprise"
    When I remove the tag "enterprise" from the lead
    Then the tag should no longer be associated with the lead
    And the lead should not be filterable by the "enterprise" tag

  Scenario: Lead permission validation
    Given I am an authenticated user but not associated with exhibitor "Acme Corp"
    When I attempt to create a lead for exhibitor "Acme Corp"
    Then I should receive a permission denied error
    And no lead should be created

  Scenario: Lead validation - required fields
    Given I am an authenticated exhibitor representative
    When I attempt to create a lead without specifying an attendee
    Then I should receive a validation error about missing attendee
    And no lead should be created

  Scenario: Create lead without notes
    Given I am an authenticated exhibitor representative
    When I attempt to create a lead without specifying notes
    Then I should receive a validation error about missing notes
    And no lead should be created