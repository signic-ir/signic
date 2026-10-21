# Test Suite - Signic Modular Platform

This document describes the testing strategy and the current test suite of Signic, a modular event registration and lead-capture platform.

## Test Locations

The test suite lives next to the code it exercises. The Laravel application root is `app/`, and each module owns a `Tests/` directory:

```
app/
├── app/Modules/
│   ├── AccessControl/Tests/TurnstileServiceTest.php
│   ├── Exhibition/Tests/LeadServiceTest.php
│   ├── Identity/Tests/OTPServiceTest.php
│   ├── Registration/Tests/AttendeeServiceTest.php
│   ├── Registration/Tests/QRServiceTest.php
│   └── Shared/Tests/CoreServiceTest.php
└── tests/
    ├── TestCase.php              # Tests\TestCase - base class for the module tests
    └── CreatesApplication.php    # Boots the Laravel application for a test
```

`app/tests/` holds only the shared base classes. The `Tests\` namespace is mapped to it through the `autoload-dev` section of `app/composer.json`.

## Running the Suite

The application runs inside the `signic-app-1` container, where the `app/` directory is mounted at `/app`:

```bash
docker exec signic-app-1 sh -c 'cd /app && ./vendor/bin/phpunit --configuration phpunit.xml'
```

The configuration file is `app/phpunit.xml`. It is git-ignored so that local overrides stay local; the shared version is tracked as `app/phpunit.xml.dist`:

```bash
cp app/phpunit.xml.dist app/phpunit.xml
```

## Test Configuration

**`phpunit.xml`** — PHPUnit 11 configuration with:
- The `Module Tests` suite, scanning `./app/Modules` for `*Test.php`
- Coverage reporting on the application modules only (`<source><include>`)
- An isolated in-memory SQLite database

### Test Environment

```env
APP_ENV=testing
APP_KEY=base64:SGVsbG8gV29ybGQhIFRoaXMgaXMgYSBzZWNyZXQga2V5IQ==
CACHE_DRIVER=array
SESSION_DRIVER=array
QUEUE_CONNECTION=sync
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
SMS_PROVIDER=null
```

## Current Coverage

| Test class | Module | Covers |
|---|---|---|
| `CoreServiceTest` | Shared | Role/permission hierarchy, permission inheritance, super-admin bypass |
| `LeadServiceTest` | Exhibition | Lead creation, interest-level defaulting, missing attendee, tag add/remove, lead filtering |
| `AttendeeServiceTest` | Registration | Attendee registration, QR token issuing and validation, QR badge creation and rotation, print job queueing |
| `OTPServiceTest` | Identity | OTP generation and delivery, verification, rejection of wrong codes, expiry, code consistency |
| `QRServiceTest` | Registration | QR token generation, signature validation, malformed and expired token rejection, payload round-trip |
| `TurnstileServiceTest` | AccessControl | Invalid QR token rejection, scan DTO parsing and defaults |

### Testing Challenges

- **No `mockery/mockery` and no `fakerphp/faker`**: neither package is vendored, and the container has no route to packagist, so they cannot be installed. Use PHPUnit's `$this->createMock()` for test doubles and hardcoded fixture values instead of faker.
- **No `$this->artisan(...)` and no `RefreshDatabase`**: `PendingCommand::mockConsoleOutput()` calls `Mockery::mock()` unconditionally, and `RefreshDatabase` routes through `$this->artisan('migrate:fresh')`. Both therefore fail with `Class "Mockery" not found`. Use `Artisan::call('migrate', ['--force' => true, '--path' => [...]])` together with the `DatabaseTransactions` trait.
- **No model factories**: the codebase ships no `*Factory.php` and uses no `HasFactory` trait. Build Eloquent rows directly (`Event::create()`, `User::create()`, `Attendee::create()`, `Exhibitor::create()`).
- **Migrations must be driver-aware**: the test database is SQLite, so PostgreSQL-only DDL (`DROP CONSTRAINT`, `::text` casts, `ALTER COLUMN ... SET DEFAULT`) has to be gated behind a `pgsql` check inside the migration.

### Dependencies

The test setup includes:
- PHPUnit 11.0+ for unit testing
- Laravel testing utilities (`DatabaseTransactions`)

### Future Enhancements
- Mailtrap for email testing
- Local Redis for lock testing
- File system for storage testing

## Next Steps

1. Add integration tests for the module controllers
2. Create API documentation tests
3. Add performance and load testing
4. Include feature flag testing
