Feature: Role-Based Access Control
  As a system administrator, I want to manage user roles and permissions so that I can control access to system functionality based on user responsibilities.

  Acceptance Criteria:
  - Assign permissions to role
  - Check user permissions
  - Check missing permissions
  - Role hierarchy inheritance
  - Permission validation on API access

  Background:
    Given the system is running
    And I have access to the Shared module
    And the following roles exist:
      | name           | description              |
      | super_admin    | Full system access       |
      | event_organizer| Manage events and attendees|
      | exhibitor      | Manage exhibitor leads   |
      | staff          | Event operations staff   |
    And the following permissions exist:
      | name                          | description                        |
      | attendee.create               | Create new attendees               |
      | attendee.view                 | View attendee information          |
      | exhibitor.lead.create         | Create leads for exhibitors        |
      | exhibitor.lead.view           | View exhibitor leads               |
      | turnstile.operate             | Operate turnstile check-in/out     |
      | user.manage                   | Manage system users                |

  Scenario: Assign permissions to role
    Given the "event_organizer" role exists
    And the "attendee.create" permission exists
    When I assign the "attendee.create" permission to the "event_organizer" role
    Then the role should have the permission
    And users with the "event_organizer" role should be able to create attendees

  Scenario: Check user permissions
    Given a user has the "event_organizer" role
    And the "event_organizer" role has the "attendee.create" permission
    When I check if the user can create attendees
    Then the check should return true
    And the user should be allowed to create attendees

  Scenario: Check missing permissions
    Given a user has the "staff" role
    And the "staff" role does not have the "exhibitor.lead.create" permission
    When I check if the user can create exhibitor leads
    Then the check should return false
    And the user should be denied from creating exhibitor leads

  Scenario: Role hierarchy inheritance
    Given the "super_admin" role inherits from all other roles
    And the "event_organizer" role has the "attendee.view" permission
    When I check if a "super_admin" user can view attendees
    Then the check should return true
    And the super_admin inherits permissions from event_organizer

  Scenario: Permission validation on API access - unauthenticated
    Given I am an unauthenticated user
    When I attempt to access the attendee creation API endpoint
    Then I should receive an authentication error
    And I should not be able to create attendees

  Scenario: Permission validation on API access - staff role
    Given I am an authenticated user with "staff" role
    When I attempt to access the attendee creation API endpoint
    Then I should receive a permission denied error
    And I should not be able to create attendees

  Scenario: Permission validation on API access - event_organizer role
    Given I am an authenticated user with "event_organizer" role
    When I attempt to access the attendee creation API endpoint
    Then I should be allowed to access the endpoint
    And I should be able to create attendees