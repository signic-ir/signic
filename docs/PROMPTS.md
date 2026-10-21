# Prompts — driving the platform from the specification

> The specification is `docs/SPECIFICATION.md`. It is the source of truth.
> These prompts are the way to hand work to an agent (or a person) so that
> the code lands matching the spec instead of drifting away from it.

---

## The golden rule

```
Never let the code become the specification.
Change docs/SPECIFICATION.md first, then change the code to match,
then prove it with the two checkers.
```

Every prompt below ends with the same two gates. Never skip them.

---

## 0. The two gates (use them after *every* change)

```bash
# Gate 1 — does the code still match the spec?
docker exec signic-app-1 php /app/scripts/check-spec-drift.php   # must exit 0

# Gate 2 — does the code still work?
docker exec signic-app-1 sh -c 'cd /app && ./vendor/bin/phpunit --configuration phpunit.xml'
# must report: OK (27 tests, 106 assertions)
```

A task is not finished until both gates are green **and** the spec document
was updated in the same commit as the code.

---

## 1. "Implement what the spec says" — feature work

```text
Read docs/SPECIFICATION.md in full before you write any code.

I want to implement: <FEATURE>. Here is the spec section that governs it:
<PASTE THE SECTION, OR SAY "§2.3">

Rules:
1. The spec is the source of truth. If the code disagrees with the spec, the
   code is wrong — do not "fix" the spec to match the code. If the spec is
   genuinely ambiguous or wrong, stop and tell me before writing code.
2. Do not add endpoints, pages, enum values, roles or permissions that the
   spec does not list. If the feature needs one, add it to the spec first and
   tell me you did.
3. Follow the existing module layout exactly:
   - Controllers   → app/app/Modules/<Module>/Http/Controllers/
   - Services      → app/app/Modules/<Module>/Services/
   - Contracts     → app/app/Modules/<Module>/Contracts/
   - DTOs          → app/app/Modules/<Module>/DTOs/
   - Models        → app/app/Modules/<Module>/Models/
   - Enums         → app/app/Modules/<Module>/Enums/
   - Migrations    → app/app/Modules/<Module>/Database/Migrations/
   - Routes        → app/app/Modules/<Module>/Routes/api.php
   - Tests         → app/app/Modules/<Module>/Tests/<Service>Test.php
4. A module must never touch another module's models or tables. Cross-module
   calls go through a Contracts\*ServiceInterface or a domain event.
5. Enum values are UPPER_CASE and must match the database CHECK constraint
   exactly. See spec §4.
6. PSR-12 for all PHP.

After you are done, run both gates and paste the output.
```

---

## 2. "Fix the drift" — bringing code back to the spec

```text
Run: docker exec signic-app-1 php /app/scripts/check-spec-drift.php

Fix every drift item it reports. Rules:
1. Fix the CODE, not the spec — unless an item is genuinely a spec error, in
   which case change the spec and tell me why.
2. Work through the drift register in docs/SPECIFICATION.md §6 first; it has
   the intended fix for each known issue.
3. Do not fix an item by deleting the check or weakening the checker.
4. Keep changes minimal and surgical. Do not refactor anything that is not
   named in a drift item.
5. After each item, re-run the drift checker so you know it is actually gone.

Finish by running both gates and paste the output.
```

---

## 3. "Write a test for X"

```text
I want a PHPUnit test for: <SUBJECT>

Read docs/SPECIFICATION.md for the contract, and read the production code of
<CLASS> for the real API. The test must assert the REAL behaviour — do not
invent a method signature, a factory, or a DTO shape that does not exist.

Rules for tests in this repository:
1. Location: app/app/Modules/<Module>/Tests/<Class>Test.php
2. Namespace: App\Modules\<Module>\Tests
3. Use PHPUnit\Framework\Attributes\Test, not the test_ method prefix.
4. There is NO mockery/mockery and NO fakerphp/faker installed, and the
   container cannot reach packagist. Use $this->createMock() for test doubles
   and hardcoded fixture values.
5. There are NO model factories. Build rows directly:
   Event::create([...]), User::create([...]), Attendee::create([...]), …
6. NEVER call $this->artisan(...) and NEVER use the RefreshDatabase trait —
   both require Mockery and will fail with 'Class "Mockery" not found'.
   Instead, in setUp():
       Artisan::call('migrate', [
           '--force' => true,
           '--path' => ['app/Modules/<Module>/Database/Migrations', ...],
       ]);
   and use the DatabaseTransactions trait.
7. Extend Illuminate\Foundation\Testing\TestCase directly and override
   createApplication() to force the sqlite :memory: connection — see
   app/app/Modules/Exhibition/Tests/LeadServiceTest.php for the pattern.
8. Migrations shared with production must run on SQLite too. If a migration
   contains PostgreSQL-only DDL, gate it behind
   DB::connection()->getDriverName() === 'pgsql'.
9. The suite must stay at 27 tests / 106 assertions or grow — never shrink.

Then run both gates and paste the output.
```

---

## 4. "Add an API endpoint"

```text
I want a new endpoint: <METHOD> <PATH> — <purpose>

Steps:
1. Add the endpoint to docs/SPECIFICATION.md §2 (the right module section),
   including its auth requirement and its request/response shape.
2. Implement it:
   - Route in app/app/Modules/<Module>/Routes/api.php
   - Request validation in app/app/Modules/<Module>/Http/Requests/
   - Controller in app/app/Modules/<Module>/Http/Controllers/
   - Business logic in app/app/Modules/<Module>/Services/
   - The module's services are injected with a Contracts\*ServiceInterface,
     never with another module's models.
3. Apply `auth:sanctum` and `throttle:api` to every protected route. The
   `otp` limiter (5/min) is only for the Identity OTP endpoints.
4. Add a test in app/app/Modules/<Module>/Tests/.
5. If the SPA needs to call it, update the page and make sure the path
   matches the spec exactly — the drift checker verifies this.

Then run both gates and paste the output.
```

---

## 5. "Add a page to the SPA"

```text
I want a new page: <URL> — <purpose>

Steps:
1. Add it to docs/SPECIFICATION.md §3.1 (the page inventory table).
2. Create app/resources/js/Pages/<Name>.vue. The FILE NAME MUST MATCH the
   Inertia component name exactly — app.js resolves a page with
   pages['./Pages/' + name + '.vue'].
3. Add the route in app/routes/web.php:
       Route::get('/<url>', fn () => Inertia::render('<Name>'));
4. Navigation uses Inertia's <Link href="…"> from @inertiajs/vue3.
   Do NOT use <router-link> — there is no Vue Router in this application
   (resources/js/router/index.js is dead code and must be deleted).
5. Never add a second id="app" to the blade layout — @inertia already emits
   the mount point.
6. Add a nav entry in app/resources/js/App.vue if the page belongs in the
   main navigation, and update the navItems array.
7. Follow the existing visual language: white cards with
   `bg-white rounded-lg shadow-sm border border-slate-200 p-6`, slate text,
   indigo primary actions. See DashboardPage.vue.

Then run both gates and paste the output.
```

---

## 6. "Explain this codebase to me"

```text
Explain the <MODULE> module of this platform.

Read, in this order:
  1. docs/SPECIFICATION.md — the contract
  2. app/app/Modules/<Module>/Routes/api.php — the exposed endpoints
  3. app/app/Modules/<Module>/Services/ — the business logic
  4. app/app/Modules/<Module>/Models/ + Database/Migrations/ — the data
  5. app/app/Modules/<Module>/Contracts/ — what it exposes to other modules
  6. app/app/Modules/<Module>/Tests/ — what behaviour is already guaranteed

For each: the public API, the invariants it maintains, the enum values it
accepts, and how another module is supposed to talk to it.
Point out any place where the code contradicts the spec.
```

---

## 7. "Prepare this for production"

```text
Audit this platform for production readiness.

Sources of truth:
  - docs/SPECIFICATION.md §7 (environment contract)
  - .env.example and app/.env.example (they must agree)
  - docker-compose.yml (every service the spec needs must exist and run)

Check, and report as a table with PASS/FAIL and the evidence:
  1. Drift  — run docker exec signic-app-1 php /app/scripts/check-spec-drift.php
  2. Tests  — run the PHPUnit suite; report the exact counts
  3. Env    — every variable in spec §7 has the specified production value
  4. Queue  — a consumer exists for QUELEIVER=database and it is running
  5. Rate limits — the api and otp limiters are actually consumed by routes
  6. CORS   — the allowed origin is exactly https://ain1.ir, never a wildcard
  7. Headers — CSP, nosniff, frame-options and HSTS are present
  8. Migrations — the database is fully migrated and matches the code
  9. Secrets — no key, password or token is committed to the repository

Do not change anything. Report only. Then propose the fixes, ordered by
launch-blocking impact, and wait for my approval.
```

---

## 8. "Act as QA on https://ain1.ir"

```text
You are doing QA on a Laravel 13 + Inertia + Vue 3 event platform.

The specification is docs/SPECIFICATION.md — it defines the API, the pages,
the enum values, the roles and the environment. The code has KNOWN drift
(listed in spec §6), so some screens are expected to be broken. Your job is
to find the failures, not to fix them.

Produce a manual test script:
  - the exact URL, method, headers and body for every API call
  - the exact expected response body and status code, taken from the spec
  - for every page: the visible text, the field labels, the button labels,
    the colours and the layout, so a human tester knows what "correct" looks
    like
  - the negative cases: missing auth, bad enum values, rate limits, tampered
    QR tokens, duplicate check-ins
  - a note for every known drift item saying "this is expected to fail
    because of drift item D<n>"

Do not guess. If a value is not in the spec and not in the code, say so
explicitly instead of inventing it.
```

---

## 9. Commit discipline

```text
Before committing:
  1. Run both gates. Both must be green.
  2. `git status` — confirm only intended files changed. No .env, no
     credentials, no build artefacts, no vendor/.
  3. Stage by explicit filename, never `git add -A`.
  4. The commit message must say WHY, not what, and must name the spec
     section it implements. If the spec changed, the spec and the code go in
     the SAME commit.
  5. Never amend a pushed commit.
```

---

## Quick reference — the invariants that break most often

| Invariant | Where it breaks |
|---|---|
| Inertia component name == Vue file name | `routes/web.php` vs `resources/js/Pages/` |
| One `id="app"` per layout | `app.blade.php`, `welcome.blade.php` |
| `<Link>`, never `<router-link>` | every page |
| API path == spec §2 path, verbatim | every page, `useAuth.js` |
| Enum values UPPER_CASE | `RegisterPage.vue` ticket type select |
| No id hardcoded in a URL | `AttendeeQRPage.vue` |
| `Artisan::call`, never `$this->artisan()` | every DB test |
| `createMock`, never `Mockery::mock()` | every test |
| Direct `Model::create`, never `::factory()` | every test |
