Feature: OTP Authentication
  As a user, I want to authenticate using OTP via SMS so that I can securely access the system.

  Acceptance Criteria:
  - Request OTP for a valid phone number should succeed
  - Verify valid OTP should authenticate the user
  - Verify invalid OTP should fail and keep user unauthenticated
  - OTP rate limiting should block excessive requests
  - Expired OTP verification should fail

  Background:
    Given the system is running
    And I have access to the Identity module
    And the SMS provider is configured for testing

  Scenario: Request OTP for phone number
    Given I am an unauthenticated user
    When I request an OTP for my phone number "+989123456789"
    Then I should receive an OTP code via SMS
    And the OTP should be valid for 5 minutes
    And the OTP should be a 6-digit numeric code

  Scenario: Verify valid OTP
    Given I have received an OTP code "123456" for phone number "+989123456789"
    When I verify the OTP code "123456" for phone number "+989123456789"
    Then the verification should succeed
    And I should be authenticated as a user
    And the OTP should be marked as used

  Scenario: Verify invalid OTP
    Given I have received an OTP code "123456" for phone number "+989123456789"
    When I verify an incorrect OTP code "654321" for phone number "+989123456789"
    Then the verification should fail
    And I should remain unauthenticated
    And the OTP should not be marked as used

  Scenario: OTP rate limiting
    Given I have requested 3 OTP codes for phone number "+989123456789" within 1 minute
    When I request another OTP for the same phone number
    Then I should receive a rate limit error
    And I should not receive another OTP until the rate limit window expires

  Scenario: Expired OTP verification
    Given I have received an OTP code "123456" for phone number "+989123456789"
    And the OTP has expired (more than 5 minutes old)
    When I verify the OTP code "123456" for phone number "+989123456789"
    Then the verification should fail with an expired OTP error
    And I should remain unauthenticated