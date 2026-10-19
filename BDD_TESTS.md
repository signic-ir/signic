# BDD Test Suite - Signic Event Registration & Lead Capture Platform

This document contains Behavior-Driven Development (BDD) tests written in Gherkin syntax for the Signic modular platform. These tests specify the expected behavior of the system from a user's perspective.

## Table of Contents
1. [Identity Module - OTP Authentication](#identity-module---otp-authentication)
2. [Registration Module - Attendee Registration](#registration-module---attendee-registration)
3. [AccessControl Module - Turnstile Check-in/Out](#accesscontrol-module---turnstile-check-inout)
4. [Exhibition Module - Lead Management](#exhibition-module---lead-management)
5. [Shared Module - Role & Permission System](#shared-module---role--permission-system)
6. [Cross-Module Workflows](#cross-module-workflows)

---

## Identity Module - OTP Authentication

### Feature: OTP Authentication
As a user, I want to authenticate using OTP via SMS so that I can securely access the system.

#### Background:
Given the system is running
And I have access to the Identity module
And the SMS provider is configured for testing

### Scenario: Request OTP for phone number
Given I am an unauthenticated user
When I request an OTP for my phone number "+989123456789"
Then I should receive an OTP code via SMS
And the OTP should be valid for 5 minutes
And the OTP should be a 6-digit numeric code

### Scenario: Verify valid OTP
Given I have received an OTP code "123456" for phone number "+989123456789"
When I verify the OTP code "123456" for phone number "+989123456789"
Then the verification should succeed
And I should be authenticated as a user
And the OTP should be marked as used

### Scenario: Verify invalid OTP
Given I have received an OTP code "123456" for phone number "+989123456789"
When I verify an incorrect OTP code "654321" for phone number "+989123456789"
Then the verification should fail
And I should remain unauthenticated
And the OTP should not be marked as used

### Scenario: OTP rate limiting
Given I have requested 3 OTP codes for phone number "+989123456789" within 1 minute
When I request another OTP for the same phone number
Then I should receive a rate limit error
And I should not receive another OTP until the rate limit window expires

### Scenario: Expired OTP verification
Given I have received an OTP code "123456" for phone number "+989123456789"
And the OTP has expired (more than 5 minutes old)
When I verify the OTP code "123456" for phone number "+989123456789"
Then the verification should fail with an expired OTP error
And I should remain unauthenticated

---

## Registration Module - Attendee Registration

### Feature: Attendee Registration
As an event organizer, I want to register attendees for events so that I can manage event participation.

#### Background:
Given the system is running
And I have access to the Registration module
And there is an active event "Tech Conference 2024"

### Scenario: Register attendee for event
Given I am an authenticated event organizer
When I register an attendee with the following details:
  | first_name | last_name | email               | phone          | ticket_type   |
  | John       | Doe       | john@example.com    | +989123456789  | GENERAL       |
And I associate the attendee with event "Tech Conference 2024"
Then the attendee should be created successfully
And the attendee status should be "CONFIRMED"
And a QR badge should be generated for the attendee
And a print job should be queued for the attendee's badge

### Scenario: Register duplicate attendee
Given I am an authenticated event organizer
And an attendee with email "john@example.com" already exists for event "Tech Conference 2024"
When I try to register another attendeer with email "john@example.com" for the same event
Then I should receive a validation error about duplicate email
And no new attendee should be created

### Scenario: Register attendee with invalid ticket type
Given I am an authenticated event organizer
When I try to register an attendee with an invalid ticket type "INVALID"
Then I should receive a validation error about invalid ticket type
And no attendee should be created

### Scenario: Generate QR token for attendee
Given an attendee exists with ID 1
When I generate a QR token for the attendee
Then I should receive a token in the format "attendee_id|random_string|signature"
And the token should be cryptographically secure
And the token should be unique for this attendee

### Scenario: Validate QR token
Given a valid QR token "1|abc123|signature" exists for attendee ID 1
When I validate the QR token "1|abc123|signature"
Then I should get the attendee ID 1 as validation result
And the token should be marked as validated

### Scenario: Validate invalid QR token
Given I have an invalid QR token "1|abc123|invalid_signature"
When I validate the invalid QR token
Then I should get a validation error
And no attendee ID should be returned

### Scenario: Expired QR token
Given a QR token was generated more than 24 hours ago
When I try to validate the expired QR token
Then I should get an expiration error
And no attendee ID should be returned

---

## AccessControl Module - Turnstile Check-in/Out

### Feature: Turnstile Access Control
As an event staff member, I want to manage attendee check-in and check-out via turnstiles so that I can control event access securely.

#### Background:
Given the system is running
And I have access to the AccessControl module
And there is an active turnstile at entrance "Main Gate"
And there is a confirmed attendee with a valid QR badge

### Scenario: Check-in attendee via turnstile
Given I am at turnstile "Main Gate"
And I scan a valid QR badge for attendee John Doe
When I attempt to check-in the attendee
Then the check-in should succeed
And the attendee status should be updated to "CHECKED_IN"
And a scan event should be recorded with type "CHECK_IN"
And the operation should be idempotent (scanning again should not create duplicate events)

### Scenario: Check-out attendee via turnstile
Given an attendee John Doe is currently checked-in
And I am at turnstile "Main Gate"
And I scan the valid QR badge for attendee John Doe
When I attempt to check-out the attendee
Then the check-out should succeed
And the attendee status should be updated to "CHECKED_OUT"
And a scan event should be recorded with type "CHECK_OUT"

### Scenario: Prevent check-out without check-in
Given an attendee John Doe has never been checked-in
And I am at turnstile "Main Gate"
And I scan the valid QR badge for attendee John Doe
When I attempt to check-out the attendee
Then the check-out should fail
And I should receive an error about invalid check-out attempt
And no scan event should be recorded

### Scenario: Prevent duplicate check-in
Given an attendee John Doe is already checked-in
And I am at turnstile "Main Gate"
And I scan the valid QR badge for attendee John Doe
When I attempt to check-in the attendee again
Then the check-in should fail
And I should receive an error about already being checked-in
And no new scan event should be recorded

### Scenario: Race condition prevention
Given multiple staff members attempt to check-in the same attendee simultaneously
When they scan the QR badge at the same time
Then only one check-in should succeed
And the others should receive appropriate errors
And exactly one scan event should be recorded

### Scenario: Invalid QR token at turnstile
Given I am at turnstile "Main Gate"
And I scan an invalid QR token
When I attempt to process the scan
Then the operation should fail
And I should receive an error about invalid QR token
And no scan event should be recorded

---

## Exhibition Module - Lead Management

### Feature: Exhibitor Lead Capture
As an exhibitor representative, I want to capture and manage leads from event attendees so that I can follow up on potential business opportunities.

#### Background:
Given the system is running
And I have access to the Exhibition module
And there is an active event "Tech Conference 2024"
And there is an exhibitor "Acme Corp" with a booth at the event
And there is an attendee "John Doe" registered for the event

### Scenario: Create lead from attendee interaction
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

### Scenario: Update lead status
Given a lead exists for attendee "John Doe" from exhibitor "Acme Corp"
And the lead status is "NEW"
When I update the lead status to "QUALIFIED"
Then the lead status should be updated successfully
And the update should be recorded in the lead's history

### Scenario: Add tag to lead
Given a lead exists for attendee "John Doe" from exhibitor "Acme Corp"
When I add the tag "enterprise" to the lead
Then the tag should be associated with the lead
And the lead should be filterable by the "enterprise" tag

### Scenario: Remove tag from lead
Given a lead exists for attendee "John Doe" from exhibitor "Acme Corp"
And the lead has the tag "enterprise"
When I remove the tag "enterprise" from the lead
Then the tag should no longer be associated with the lead
And the lead should not be filterable by the "enterprise" tag

### Scenario: Lead permission validation
Given I am an authenticated user but not associated with exhibitor "Acme Corp"
When I attempt to create a lead for exhibitor "Acme Corp"
Then I should receive a permission denied error
And no lead should be created

### Scenario: Lead validation - required fields
Given I am an authenticated exhibitor representative
When I attempt to create a lead without specifying an attendee
Then I should receive a validation error about missing attendee
And no lead should be created

When I attempt to create a lead without specifying notes
Then I should receive a validation error about missing notes
And no lead should be created

---

## Shared Module - Role & Permission System

### Feature: Role-Based Access Control
As a system administrator, I want to manage user roles and permissions so that I can control access to system functionality based on user responsibilities.

#### Background:
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

### Scenario: Assign permissions to role
Given the "event_organizer" role exists
And the "attendee.create" permission exists
When I assign the "attendee.create" permission to the "event_organizer" role
Then the role should have the permission
And users with the "event_organizer" role should be able to create attendees

### Scenario: Check user permissions
Given a user has the "event_organizer" role
And the "event_organizer" role has the "attendee.create" permission
When I check if the user can create attendees
Then the check should return true
And the user should be allowed to create attendees

### Scenario: Check missing permissions
Given a user has the "staff" role
And the "staff" role does not have the "exhibitor.lead.create" permission
When I check if the user can create exhibitor leads
Then the check should return false
And the user should be denied from creating exhibitor leads

### Scenario: Role hierarchy inheritance
Given the "super_admin" role inherits from all other roles
And the "event_organizer" role has the "attendee.view" permission
When I check if a "super_admin" user can view attendees
Then the check should return true
Because the super_admin inherits permissions from event_organizer

### Scenario: Permission validation on API access
Given I am an unauthenticated user
When I attempt to access the attendee creation API endpoint
Then I should receive an authentication error
And I should not be able to create attendees

Given I am an authenticated user with "staff" role
When I attempt to access the attendee creation API endpoint
Then I should receive a permission denied error
And I should not be able to create attendees

Given I am an authenticated user with "event_organizer" role
When I attempt to access the attendee creation API endpoint
Then I should be allowed to access the endpoint
And I should be able to create attendees

---

## Cross-Module Workflows

### Feature: Complete Event Lifecycle
As an event organizer, I want to manage the complete event lifecycle from registration to lead capture so that I can run successful events.

#### Background:
Given the system is running
And I have access to all modules
And there is an active event "Tech Conference 2024"
And there is an exhibitor "Acme Corp" with a booth at the event
And there are turnstiles configured at all entrances

### Scenario: Complete attendee journey
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

### Scenario: Event analytics and reporting
Given multiple attendees have registered for event "Tech Conference 2024"
And multiple attendees have checked-in and checked-out
And multiple leads have been captured by exhibitors
When I generate an event report
Then I should see:
  - Total registered attendees
  - Total checked-in attendees
  - Total leads captured
  - Conversion rate from registration to check-in
  - Leads per exhibitor breakdown
  - Popular ticket types

### Scenario: System cleanup after event
Given event "Tech Conference 2024" has ended
When I run the expired event cleanup process
Then events older than 30 days should be archived
And expired QR tokens should be invalidated
And temporary data should be cleaned up
But historical data should be preserved for reporting
