# ain1.ir Visitor Landing Page Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use compose:subagent (recommended) or compose:execute to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add public visitor-facing pages to the Signic (ain1.ir) platform: a landing page, QR code guide, and postal card print flow.

**Architecture:** New public Inertia pages served without authentication, backed by the existing Registration API for QR generation and attendee lookup.

**Tech Stack:** Laravel 13 (PHP), Inertia + Vue 3, Tailwind CSS, Inertia `Link`, axios.

---

## Design anchors referenced

- [S1] Problem / context
- [S2] Solution overview
- [S3] Landing page design
- [S4] QR code guide design
- [S5] Postal card print design
- [S6] Implementation approach

---

## File structure

| File | Action | Responsibility |
|---|---|---|
| `app/routes/web.php` | Modify | Add public routes: `/` (landing), `/qr-guide` (guide), `/postal-card/{attendeeId}` (print) |
| `app/resources/js/Pages/LandingPage.vue` | Create | Public landing page (hero, how-it-works, CTA) |
| `app/resources/js/Pages/QrGuidePage.vue` | Create | Public QR guide (steps, sample QR, FAQ) |
| `app/resources/js/Pages/PostalCardPage.vue` | Create | Public postal card view: fetch attendee + QR, print UI |
| `app/resources/js/Pages/DashboardPage.vue` | Modify | Move to `/dashboard` (protected) |
| `app/resources/js/App.vue` | Modify | Nav for public pages; hide when unauthenticated |

---

## Tasks

### Task 1: Add public routes

**Covers:** [S2], [S6]

**Files:**
- Modify: `app/routes/web.php:6-8`

- [ ] **Step 1: Write the failing test**

```php
// tests/Feature/PublicVisitorPagesTest.php
public function test_landing_page_is_public(): void
{
    $response = $this->get('/');
    $response->assertOk();
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `docker compose exec app php artisan test tests/Feature/PublicVisitorPagesTest.php`

Expected: FAIL — `/` requires auth (redirects to `/login`).

- [ ] **Step 3: Write minimal implementation**

In `app/routes/web.php`, replace the current `/` closure to render `LandingPage`, add `/qr-guide`, add `/postal-card/{attendeeId}` (redirects to Inertia page), and move the old dashboard render to `/dashboard`.

- [ ] **Step 4: Run tests and verify pass**

- [ ] **Step 5: Commit**

---

### Task 2: Create LandingPage.vue

**Covers:** [S3]

**Files:**
- Create: `app/resources/js/Pages/LandingPage.vue`

- [ ] **Step 1: Write the failing test**

Not applicable for pure Vue rendering — covered by feature test in Task 1.

- [ ] **Step 2: Implement**

Create `LandingPage.vue` with:
- Hero: event branding, tagline, date/venue
- "How it works": 3 steps (Register → Get QR → Scan)
- CTA: "I have my badge / Register now" → `/register`; "How do I scan?" → `/qr-guide`

- [ ] **Step 3: Commit**

---

### Task 3: Create QrGuidePage.vue

**Covers:** [S4]

**Files:**
- Create: `app/resources/js/Pages/QrGuidePage.vue`

- [ ] **Step 1: Implement**

Create `QrGuidePage.vue` with:
- Step-by-step visual guide (Register → Get QR → Scan at check-in)
- Sample QR code placeholder
- Instructions for camera apps
- Link back to landing page and to register

- [ ] **Step 2: Commit**

---

### Task 4: Create PostalCardPage.vue

**Covers:** [S5]

**Files:**
- Create: `app/resources/js/Pages/PostalCardPage.vue`

- [ ] **Step 1: Implement**

Create `PostalCardPage.vue` that:
- Reads `attendeeId` from route params
- Fetches `GET /api/registration/attendees/{attendeeId}/qr`
- Shows printable postal card layout: attendee name, ticket type, QR code, event branding
- Print button: `window.print()` on print-optimized area

- [ ] **Step 2: Commit**

---

### Task 5: Update App.vue navigation & dashboard route

**Covers:** [S2], [S6]

**Files:**
- Modify: `app/resources/js/Pages/DashboardPage.vue`
- Modify: `app/resources/js/App.vue`

- [ ] **Step 1: Move Dashboard to `/dashboard`** — add `requireAuth()` stays; nav changes.

- [ ] **Step 2: Update `App.vue`** — nav shows public pages (Landing, QR Guide) when unauthenticated; show Dashboard etc. when authenticated.

- [ ] **Step 3: Commit**

---

## Verification

- Run drift checker if spec touched — no spec change planned here.
- Run PHPUnit feature tests — new public page tests must pass.
- Frontend: run `npm run build` (or vite build) to verify Vue components compile.

---

## Out of scope (YAGNI)

- Backend PDF library installation (client-side print via window.print()).
- Email/SMS flows for QR badges to unauthenticated visitors.
- Analytics on landing page visits.

---

## Spec coverage check (self-review)

| Spec section | Covered by task(s) |
|---|---|
| [S1] Problem | Task 1 context |
| [S2] Solution overview | Task 1, Task 5 |
| [S3] Landing page design | Task 2 |
| [S4] QR code guide design | Task 3 |
| [S5] Postal card print design | Task 4 |
| [S6] Implementation approach | Task 1, Task 5 |

No gaps.

---

## Placeholder scan

No TBDs. Content like event name, date, venue, tagline to be filled in by user.

---

## Next step

Use compose:plan output → invoke compose:execute to implement task-by-task.
