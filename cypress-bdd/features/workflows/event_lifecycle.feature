Feature: Complete Event Lifecycle
  As an event organizer, I want to manage the complete event lifecycle from registration to lead capture so that I can run successful events.

  Acceptance Criteria:
  - Complete attendee journey from OTP to check-out
  - Event analytics and reporting
  - System cleanup after event

  Background:
    Given the system is running
    And I have access to all modules
    And there is an active event "Tech Conference 2024"
    And there is an exhibitor "Acme Corp" with a booth at the event
    And there are turnstiles configured at all entrances

  Scenario: Complete attendee journey
    Given I am an unauthenticated user
    When I request an OTP for phone number "+989123456789"
    And I receive OTP code "123456"
    And I verify the OTP code "123456"
    Then I should be authenticated as a user

    Given I am now an authenticated event organizer
    When I register an attendee with:
      | first_name | last_name | email               | phone          | ticket_type   |
      | Jane       | Smith     | jane@example.com    | +989987654321  | VIP           |
    And I associate the attendee with event "Tech Conference 2024"
    Then the attendee should be created successfully
    And a QR badge should be generated for the attendee
    And a print job should be queued

    Given the attendee Jane Smith has a valid QR badge
    When I scan the QR badge at turnstile "Main Gate" for check-in
    Then the check-in should succeed
    And the attendee status should be "CHECKED_IN"

    Given the attendee Jane Smith is checked-in
    When I visit exhibitor "Acme Corp" booth
    And I capture a lead for attendee Jane Smith with:
      | interest_level | notes                          |
      | MEDIUM         | Interested in product demo     |
    And I associate the lead with booth "Acme Corp Booth"
    Then the lead should be created successfully
    And the lead should be associated with the correct attendee

    When I scan the QR badge at turnstile "Main Gate" for check-out
    Then the check-out should succeed
    And the attendee status should be "CHECKED_OUT"

  Scenario: Event analytics and reporting
    Given multiple attendees have registered for event "Tech Conference 2024"
    And multiple attendees have checked-in and checked-out
    And multiple leads have been captured by exhibitors
    When I generate an event report
    Then I should see:
      | Total registered attendees |
      | Total checked-in attendees |
      | Total leads captured |
      | Conversion rate from registration to check-in |
      | Leads per exhibitor breakdown |
      | Popular ticket types |

  Scenario: System cleanup after event
    Given event "Tech Conference 2024" has ended
    When I run the expired event cleanup process
    Then events older than 30 days should be archived
    And expired QR tokens should be invalidated
    And temporary data should be cleaned up
    But historical data should be preserved for reporting