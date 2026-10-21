# Signic — Product Specification (Single Source of Truth)

> This document defines **what must exist**. Code is an implementation of this
> document, never the other way round. When code and this document disagree,
> the document wins and the code is the bug.
>
> Every claim in here is machine-checkable. Run the drift checker to see
> whether the code still honours it:
>
> ```bash
> docker exec signic-app-1 php /app/scripts/check-spec-drift.php
> ```
>
> Exit code `0` = code matches spec. Exit code `1` = drift found.

---

## 1. Product shape

Signic is an **Event Registration & Lead Capture Platform**. It is a Laravel 13
modular monolith with a Vue 3 + Inertia SPA on the front.

Five modules, each self-contained:

| Module | Namespace | Responsibility |
|---|---|---|
| Identity | `App\Modules\Identity` | OTP authentication over SMS, current-user profile, Sanctum tokens |
| Registration | `App\Modules\Registration` | Events, attendees, QR badges, badge print jobs |
| AccessControl | `App\Modules\AccessControl` | Turnstile check-in/out with Redis atomic locks, scan events |
| Exhibition | `App\Modules\Exhibition` | Exhibitors, booths, exhibitor members, leads, lead tags |
| Shared | `App\Modules\Shared` | Roles, permissions, user↔role assignment |

**Module boundary rule** — a module must never reach into another module's
models or tables. Cross-module communication goes through
`Contracts\*ServiceInterface` or domain events.

---

## 2. Backend contract — the HTTP API

The API is the boundary between the SPA and the platform. The SPA may only
call endpoints listed here. **Anything else is a 404.**

### 2.1 Identity — `/api/identity`

| Method | Path | Auth | Purpose |
|---|---|---|---|
| POST | `/api/identity/otp/request` | no | Send an OTP. Body `{ phone }`. Rate limited `throttle:otp` (5/min) + 3/min per phone. |
| POST | `/api/identity/otp/verify` | no | Verify. Body `{ phone, otp }`. Rate limited `throttle:otp` (5/min) + 5/min per phone. |
| GET | `/api/identity/me` | yes | Current user. Returns `{ user: {...} }`. |
| POST | `/api/identity/logout` | yes | Revoke the current token. |

**Response shapes**

```
POST /api/identity/otp/request  → 200 { "message": "OTP sent successfully", "phone": "+98..." }
POST /api/identity/otp/verify   → 200 { "message": "Authentication successful",
                                          "access_token": "<plainTextToken>",
                                          "token_type": "Bearer",
                                          "user": { ... } }
                                  → 401 { "message": "Invalid or expired OTP" }
                                  → 429 { "message": "Too many verification attempts." }
GET  /api/identity/me           → 200 { "user": { ... } }
```

The OTP is a 6-digit numeric code, valid for 5 minutes.

### 2.2 Registration — `/api/registration` (also mounted at `/registration`)

| Method | Path | Purpose |
|---|---|---|
| POST | `/api/registration/attendees` | Register an attendee. Returns `{ attendee, token, print_job }`. |
| GET | `/api/registration/attendees` | List attendees. |
| GET | `/api/registration/attendees/{id}` | One attendee. |
| GET | `/api/registration/attendees/{id}/qr` | The attendee's QR badge (token + image URL). |
| POST | `/api/registration/attendees/{id}/qr/expire` | Expire the badge. |
| POST | `/api/registration/qr/validate` | Validate a token. Body `{ token }`. |
| GET | `/api/registration/events` | List events. |
| POST | `/api/registration/events` | Create an event. |
| GET | `/api/registration/events/{id}` | One event. |
| GET | `/api/registration/events/report` | Event analytics. |
| GET | `/api/registration/events/{id}/report` | Per-event analytics. |
| POST | `/api/registration/events/cleanup` | Archive expired events. |

**All Registration routes require `auth:sanctum` + `throttle:api`.**

### 2.3 AccessControl — `/api/access` (also mounted at `/access`)

| Method | Path | Purpose |
|---|---|---|
| POST | `/api/access/checkin` | Check an attendee in. Body `{ qr_token, turnstile_id }`. |
| POST | `/api/access/checkout` | Check an attendee out. |
| GET | `/api/access/scan-events` | Scan history. |
| GET | `/api/access/turnstiles` | List turnstiles. |
| POST | `/api/access/turnstiles` | Create a turnstile. |
| GET | `/api/access/attendees/{id}/status` | Attendance status of one attendee. |

> **The prefix is `access`, not `accesscontrol`.** The module is named
> AccessControl but its URL prefix is `access`.

### 2.4 Exhibition — `/api/exhibition` (also mounted at `/exhibition`)

| Method | Path | Purpose |
|---|---|---|
| GET | `/api/exhibition/exhibitors` | List exhibitors. |
| POST | `/api/exhibition/exhibitors` | Create an exhibitor. |
| GET | `/api/exhibition/exhibitors/{exhibitor}` | One exhibitor. |
| POST | `/api/exhibition/exhibitors/{exhibitor}/members` | Add an exhibitor member. |
| GET | `/api/exhibition/booths` | List booths. |
| POST | `/api/exhibition/booths` | Create a booth. |
| GET | `/api/exhibition/booths/{booth}` | One booth. |
| GET | `/api/exhibition/leads` | List leads (filterable). |
| POST | `/api/exhibition/leads` | Capture a lead. |
| GET | `/api/exhibition/leads/{lead}` | One lead. |
| PUT/PATCH | `/api/exhibition/leads/{lead}` | Update a lead (status, tags, …). |
| GET | `/api/exhibition/leads/{lead}/history` | Lead history. |
| POST | `/api/exhibition/leads/{lead}/tags` | Add a tag. |
| DELETE | `/api/exhibition/leads/{lead}/tags/{tag}` | Remove a tag. |

### 2.5 Shared — `/api/shared`

| Method | Path | Purpose |
|---|---|---|
| GET/POST | `/api/shared/roles` | List / create roles. |
| GET | `/api/shared/roles/{role}` | One role. |
| POST | `/api/shared/roles/{role}/permissions` | Grant a permission. |
| DELETE | `/api/shared/roles/{role}/permissions/{permission}` | Revoke a permission. |
| POST | `/api/shared/roles/{role}/inherit` | Inherit a role. |
| POST | `/api/shared/roles/{role}/inherit-all` | Inherit everything. |
| GET/POST | `/api/shared/permissions` | List / create permissions. |
| GET | `/api/shared/permissions/check` | Check a permission. |
| GET | `/api/shared/users/{user}` | One user. |
| GET/POST | `/api/shared/users/{user}/roles` | List / assign roles. |
| DELETE | `/api/shared/users/{user}/roles/{role}` | Remove a role. |
| GET | `/api/shared/users/{user}/permissions/{permission}` | Check a user's permission. |

### 2.6 Platform

| Method | Path | Purpose |
|---|---|---|
| GET | `/api/health` | Liveness. Returns `{ "status": "ok", "service": "signic" }`. |

---

## 3. Frontend contract — pages

The SPA is **Inertia + Vue 3**. There is exactly **one** router: Inertia's.
Vue Router is **not** used (see §6, D3).

### 3.1 Page inventory

| URL | Inertia component | Vue file | Auth | Purpose |
|---|---|---|---|---|
| `/` | `Dashboard` | `Pages/DashboardPage.vue` | yes | Landing dashboard |
| `/login` | `LoginPage` | `Pages/LoginPage.vue` | no | Phone + OTP sign-in |
| `/register` | `RegisterPage` | `Pages/RegisterPage.vue` | yes | Register an attendee |
| `/qr` | `AttendeeQRPage` | `Pages/AttendeeQRPage.vue` | yes | Show the attendee's QR badge |
| `/checkin` | `CheckInPage` | `Pages/CheckInPage.vue` | yes | Check in / out |
| `/exhibitors` | `ExhibitorPage` | `Pages/ExhibitorPage.vue` | yes | List / create exhibitors |

> **The Inertia component name and the Vue file name must match exactly.**
> `app.js` resolves a page with `pages['./Pages/' + name + '.vue']`, so
> `Inertia::render('Dashboard')` requires a file called `Dashboard.vue`.
> Either the render call or the file must be renamed — but they must agree.

### 3.2 Layout contract

`app/resources/views/app.blade.php` must contain **exactly one** `id="app"`
element. The `@inertia` directive already emits the mount point together with
the `data-page` payload, so the layout must **not** declare its own
`<div id="app"></div>`.

```blade
<body class="antialiased">
    @inertia
</body>
```

### 3.3 Navigation contract

Navigation is done with **Inertia `<Link>`** (`@inertiajs/vue3`), not
`<router-link>`. Import it per component:

```js
import { Link } from '@inertiajs/vue3';
```

### 3.4 Auth guard contract

The SPA keeps `signic_token` and `signic_user` in `localStorage`. Guards are
enforced in two places:

1. **Server** — every protected API route carries `auth:sanctum`.
2. **Client** — each Inertia page checks for a token and redirects to
   `/login` when absent. This is implemented per page, **not** via a router
   guard (there is no client router).

### 3.5 Authenticated request contract

Every authenticated request carries:

```
Authorization: Bearer <access_token>
```

The token comes from `POST /api/identity/otp/verify` → `access_token`.

---

## 4. Domain vocabulary — the canonical values

Case matters. The database enforces it with CHECK constraints.

### 4.1 `attendees.ticket_type`

`GENERAL` · `VIP` · `SPEAKER` · `PRESS` · `ORGANIZER` · `VOLUNTEER`

### 4.2 `attendees.status`

`REGISTERED` · `CONFIRMED` · `ATTENDED` · `NO_SHOW` · `CANCELLED` ·
`CHECKED_IN` · `CHECKED_OUT`

A newly registered attendee is `CONFIRMED`.

### 4.3 `events.status`

`DRAFT` · `ACTIVE` · `PAUSED` · `COMPLETED` · `CANCELLED`

### 4.4 `leads.interest_level`

`LOW` · `MEDIUM` · `HIGH` — defaults to `MEDIUM` when omitted.

### 4.5 `leads.status`

`NEW` · `QUALIFIED` · `CONTACTED` · `CONVERTED` · `LOST` — defaults to `NEW`.

### 4.6 `print_jobs.status`

`pending` · `printing` · `printed` · `failed` — **lower case**, this one is
different from the others.

---

## 5. Roles and permissions

Seeded by `App\Modules\Shared\Database\Seeders\RolePermissionSeeder`.

**Permissions**

| Name | Description |
|---|---|
| `attendee.create` | Create new attendees |
| `attendee.view` | View attendee information |
| `exhibitor.lead.create` | Create leads for exhibitors |
| `exhibitor.lead.view` | View exhibitor leads |
| `turnstile.operate` | Operate turnstile check-in/out |
| `user.manage` | Manage system users |

**Roles**

| Name | Description | Holds |
|---|---|---|
| `super_admin` | Full system access | everything (`inherits_all`) |
| `event_organizer` | Manage events and attendees | `attendee.create`, `attendee.view` |
| `exhibitor` | Manage exhibitor leads | `exhibitor.lead.create`, `exhibitor.lead.view` |
| `staff` | Event operations staff | `turnstile.operate`, `attendee.view` |

---

## 6. Drift register — known code↔spec mismatches

Everything in this list is a **defect in the code**, not a decision to change
the spec. Each entry names the fix that restores spec compliance.

| # | Where | Defect | Fix |
|---|---|---|---|
| **D1** | `routes/web.php:9` | `Inertia::render('Dashboard')` but the file is `Pages/DashboardPage.vue` → Inertia cannot resolve the page | Rename the render call to `DashboardPage`, or rename the file to `Dashboard.vue` — and apply the same to `app.blade.php` consumers |
| **D2** | `app.blade.php:20`, `welcome.blade.php:20` | Both declare `<div id="app"></div>` **and** `@inertia`, producing two elements with `id="app"`. `getElementById('app')` returns the empty one, which has no `data-page` | Delete the manual `<div id="app"></div>`; keep only `@inertia` |
| **D3** | `app.js` | Vue Router is never registered, yet `router-link` is used in `App.vue`, `DashboardPage.vue`, `LoginPage.vue`. `router/index.js` is dead code and references a non-existent `CheckOutPage.vue` | Delete `router/index.js`; replace every `<router-link to=…>` with Inertia `<Link href=…>` |
| **D4** | `LoginPage.vue:107` | Calls `POST /api/identity/otp/send` — the route is `/otp/request` | Change the path to `/api/identity/otp/request` |
| **D5** | `useAuth.js:30` | Calls `identity/profile` (no `/api` prefix, wrong name) — the route is `/api/identity/me` | Change the path to `/api/identity/me` |
| **D6** | `CheckInPage.vue:76,106` | Calls `/api/accesscontrol/checkin` and `/api/accesscontrol/checkout` — the prefix is `/api/access` | Change both paths |
| **D7** | `RegisterPage.vue:143` | `event_id: 1` is hardcoded | Load the event list (`GET /api/registration/events`) and let the user pick; fall back to the first event |
| **D8** | `AttendeeQRPage.vue:118` | Fetches `/api/registration/attendees/1/qr` — id `1` is hardcoded and the `?attendee_id=` query is ignored | Read `attendee_id` from the query string |
| **D9** | `RegisterPage.vue:80-85` | Ticket type option values are lower case (`general`, `vip`, …) but the DB enum is upper case — the Postgres CHECK constraint rejects them | Use the upper-case values from §4.1 |
| **D10** | `routes/web.php` | No `/checkout` route, but the dead Vue router declared one | Remove the dead route (the SPA has no check-out page) or add a page + route |

---

## 7. Environment contract

| Variable | Production value | Notes |
|---|---|---|
| `APP_ENV` | `production` | |
| `APP_DEBUG` | `false` | |
| `APP_URL` | `https://ain1.ir` | |
| `DB_CONNECTION` | `pgsql` | |
| `QUEUE_CONNECTION` | `database` | needs a `queue:work` consumer |
| `MAIL_MAILER` | `log` | |
| `SESSION_DRIVER` | `redis` | |
| `CACHE_STORE` | `redis` | |
| `REDIS_*` | set | required for turnstile atomic locks |
| `SMS_PROVIDER` | `null` | OTP codes are written to the log, not sent |
| `FRONTEND_URL` | `https://ain1.ir` | drives `config/cors.php` |

**Rate limits**

| Limiter | Limit | Applied to |
|---|---|---|
| `api` | 60/min | every authenticated module route + `throttleApi` globally |
| `otp` | 5/min | `/otp/request`, `/otp/verify` (per IP) |

**The `worker` service must be running** for `print_jobs` to leave `pending`.

---

## 8. How to change this platform safely

1. Change **this document** first.
2. Update the code to match.
3. Run the drift checker — it must exit `0`.
4. Run the PHPUnit suite — `27 tests, 106 assertions`, all green.
5. Commit the document and the code **together**, so they never diverge again.

Never let the code become the specification by accident.
