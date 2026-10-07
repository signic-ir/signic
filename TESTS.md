# Phase 3-4 Test Suite - Signic Modular Platform

This document outlines the testing strategy for Signic, a modular event registration and lead-capture platform.

## Phase 3: Module Infrastructure Tests

### Identity Module Tests
- **OTPServiceTest.php**: Unit tests for OTP generation, SMS sending, and verification
- **Unit/OTPTest.php**: Modern Pest tests for OTP functionality

### Registration Module Tests
- **QRServiceTest.php**: QR token generation, validation, and QR code generation
- **Feature/RegistrationFlowTest.php**: Integration tests for attendee registration flow

### AccessControl Module Tests
- **TurnstileServiceTest.php**: Turnstile check-in/out operations, QR validation, and race condition handling

### Exhibition Module Tests
- **LeadServiceTest.php**: Lead creation DTO, lead model, and lead tag model

### Shared Module Tests
- **RolePermissionTest.php**: Role and permission model structure

## Phase 4: Testing Architecture

### Test Directory Structure
```
tests/
├── TestCase.php              # Base test case
├── CreatesApplication.php    # Application bootstraps
├── Pest.php                  # Pest framework configuration
├── TestCase.php              # Laravel TestCase
├── Feature/
│   ├── RegistrationFlowTest.php
│   └── AuthenticationTest.php
├── Unit/
│   ├── OTPTest.php
│   └── ValidationTest.php
├── Modules/
│   ├── Identity/
│   │   ├── OTPServiceTest.php
│   │   └── Unit/OTPTest.php
│   ├── Registration/
│   │   └── QRServiceTest.php
│   ├── AccessControl/
│   │   └── TurnstileServiceTest.php
│   ├── Exhibition/
│   │   └── LeadServiceTest.php
│   └── Shared/
│       └── RolePermissionTest.php
```

### Test Categories

1. **Unit Tests** (Tests/Unit/)
   - Core logic verification
   - Helper functions
   - Utility services

2. **Module Tests** (Tests/Modules/*/)
   - Individual module service testing
   - Contract adherence verification
   - Integration between service components

3. **Feature Tests** (Tests/Feature/)
   - End-to-end workflow testing
   - Controller action verification
   - Database interaction tests

### Testing Coverage Strategy

#### Identity Module
- OTP generation (length, format, uniqueness)
- SMS adapter interface compliance
- OTP cache storage and retrieval
- OTP verification (success, failure, expired)
- Rate limiting (where implemented)

#### Registration Module
- QR token generation (attendee ID + nonce)
- QR token signature validation
- QR token expiration handling
- Base64 encoding/decoding
- Malformed token rejection

#### AccessControl Module
- QR token validation integration
- Turnstile race condition prevention (Redis locks)
- Idempotent check-in/check-out
- Database persistence verification
- Concurrent access safety

#### Exhibition Module
- DTO validation and structure
- Lead creation workflows
- Lead status transitions
- Relationship mapping

#### Shared Module
- Role hierarchy and permissions
- Pivot table relationships
- Guard name validation
- Permission inheritance

### Test Configuration

**phpunit.xml**: PHPUnit configuration with:
- 4 parallel test processes
- Custom cache and queue configuration
- Coverage reporting on application modules only

**Pest.php**: Pest framework configuration
- Module-specific test groupings
- Database refresh for each test suite

### Test Environment

```env
APP_ENV=testing
APP_KEY=base64:TESTKEY...
CACHE_DRIVER=array
SESSION_DRIVER=array
QUEUE_CONNECTION=sync
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
SMS_PROVIDER=null
```

### Key Test Files Created

1. **tests/Modules/Identity/OTPServiceTest.php**
   - Tests OTP generation, sending, and verification
   - Mock SMS adapter for testing

2. **tests/Modules/Registration/QRServiceTest.php**
   - Tests QR token lifecycle
   - Signature validation
   - Error handling

3. **tests/Modules/AccessControl/TurnstileServiceTest.php**
   - Turnstile check-in/out operations
   - Race condition handling
   - Token validation integration

4. **tests/Modules/Exhibition/LeadServiceTest.php**
   - DTO structure verification
   - Lead model properties

5. **tests/Modules/Shared/RolePermissionTest.php**
   - Role and permission model structure

6. **tests/Unit/OTPTest.php**
   - Modern Pest unit tests for OTP logic

7. **tests/Feature/RegistrationFlowTest.php**
   - End-to-end registration flow test

## Implementation Notes

### Testing Challenges
- No runtime environment to execute tests
- Tests created for documentation and validation purposes only
- Configuration suitable for local development

### Dependencies
The test setup includes:
- PHPUnit 11.0+ for unit testing
- Pest 3.0+ for modern testing approach
- Laravel testing utilities (RefreshDatabase)

### Future Enhancements
- Database migrations for testing
- Mailtrap for email testing
- Local Redis for lock testing
- File system for storage testing

## Next Steps

1. Run tests after setting up Laravel environment
2. Add more integration tests for controllers
3. Create API documentation tests
4. Add performance and load testing
5. Include feature flag testing