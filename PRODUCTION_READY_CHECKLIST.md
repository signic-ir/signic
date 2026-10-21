# Signic Production Readiness Checklist — ain1.ir

> Prioritized action items for **Fullstack Developers** and **DevOps**
> Target: `ain1.ir` live. Must hold before traffic reaches production.
> Verified against `app/` layout (Laravel app root is `app/`, routes live at
> `app/routes/`, views at `app/resources/views/`). Do not edit files outside this layout without updating both copies.

---

## ⚠️ Critical — Fix or site breaks before launch

### 1. HTTPS/TLS handled by ArvanCloud edge — no nginx TLS block needed
User confirmed `ain1.ir` HTTPS is handled by ArvanCloud edge (external TLS termination). **Do NOT add an nginx `listen 443 ssl` block or cert paths** — that would create double termination / redirect loops.
- **Keep the HTTP→HTTPS 301 redirect only if nginx needs to enforce HTTPS itself** — otherwise remove `default.conf:26-29` (`return 301 https://...`) since ArvanCloud already terminates TLS and redirects.
- Bind host ports 5432/6379/9000/9001 to `127.0.0.1` in `docker-compose.yml` (or move them off the host entirely) — they are currently published publicly.

### 2. Queue worker missing — jobs stall forever
User directed: **handle queue by database** (not redis).
- `QUEUE_CONNECTION=database` requires a `database` connection in `app/config/queue.php` (currently only `redis` defined) and a `jobs` table migration (none exists).
- Create the Laravel `jobs` table migration (`php artisan queue:table` → `database/migrations`) and add a `database` connection to `app/config/queue.php`.
- **No queue worker process is running** in `docker-compose.yml` — nothing consumes the queue. Add a `worker` service mirroring the `app` service running `php artisan queue:work database --tries=3`.
- Note: `PrintBadgeJob` (`app/app/Modules/Registration/Jobs/PrintBadgeJob.php`) does NOT implement `ShouldQueue` and is never dispatched — switching queue driver has zero immediate impact, but the `jobs` table is still needed for the driver to work.

### 3. Mail temporarily switched to `log` mailer
User directed: **switch to log temporarily for mail**.
- Set `MAIL_MAILER=log` in `.env` (currently `smtp`). The `log` mailer is already defined in `app/config/mail.php:19-22`.
- Keep Mailpit as a **dev/staging-only** profile, not in production compose.
- For production later: replace with a real SMTP provider, encrypt with TLS, rotate `MAIL_PASSWORD`.

### 4. Rate limiting — API write endpoints have no throttle
Rate limiting belongs at the **top of this checklist** (per user directive). Current state:
- `app/app/Http/Kernel.php:40-43` `api` middleware group has **no `throttle`** — the named `RateLimiter::for('api')` (60/min) and `for('otp')` (5/min) in `RouteServiceProvider:37-43` are **never referenced by any route**.
- OTP routes use `throttle:60,1,otp_request` where the 3rd arg is a cache-key **prefix**, not a limiter name — so OTP actually throttles at 60/min, not the intended 5/min.
- `RateLimitOTP` middleware (5/min) exists but is **never applied**; `RateLimitScan` (120/min) is used only on AccessControl check-in/checkout controller constructors.
- Add `->middleware(['throttle:api'])` to the `api` group in `app/app/Http/Kernel.php`, or apply `RateLimiter::for('api')` / `RateLimiter::for('otp')` to each route. Recommended sane limits: checkin 5/min, attendee 5/min, leads 20/min, OTP 5/min.

### 5. CORS misconfiguration violates spec & breaks frontend
- `nginx/conf.d/default.conf:20-21` publishes `Access-Control-Allow-Origin: *` **with** `Access-Control-Allow-Credentials: true` — browsers reject this combination. Fix: restrict origin to `https://ain1.ir`.
- `app/config/cors.php:9` `allowed_origins` defaults to `http://localhost:3000`; confirm `FRONTEND_URL=https://ain1.ir` is exported from `.env` in production.

### 6. Production `APP_KEY` & secrets exist in git history
User directed: **"I'll change app key later"** — do NOT rotate now.
- `APP_KEY=base64:Vq1XJ7ZXKdY8J6t9G5h3PqR6sT2uV4wB8nLmNpQrStU=` was committed into the repo and remains in git history.
- `.env` is now gitignored (`git rm --cached .env`); `.env.example` committed with blank `APP_KEY=` and placeholder `DB_PASSWORD=change-me` / `AWS_SECRET_ACCESS_KEY=change-me`.
- Rotation is a **todo**, not an action — leave as-is until user runs `php artisan key:generate`.

---

## P1 — Important, no crash yet

### 7. Runtime readiness of deployments
- [ ] `php artisan migrate --force` in every deployment (add a deploy step; currently not automated).
- [ ] `config:cache`, `route:cache`, `view:cache` run after release (currently only in checks, not enforced on startup).
- [ ] Rebuild assets inside the prod image correctly (real `package.json`/`package-lock.json` copied instead of the inline heredoc hack in `Dockerfile`); rebuild once at scale (see item 17).

### 8. Image hygiene
- [ ] Add `app/.dockerignore` (exclude `node_modules`, `vendor`, `pgdata`, `*.log`, `.git`) to cut multi-GB layers.
- [ ] Pin all images: `docker-compose.yml` uses `node:20-alpine`, `postgres:16-alpine`, `redis:7-alpine`, `minio/minio:latest`, `axllent/mailpit:latest` — change to pinned tags.
- [ ] Remove the `./app:/app` volume mount on the `app` prod service; it overrides the built image and ships host dev deps into prod. Let volumes persist as `/app/storage/app:/app/storage/app` only.

### 9. Security middleware gaps
- [ ] `bootstrap/app.php` lacks `TrustedProxies` + `TrustMiddlewareCookieMiddleware` — behind nginx, request IPs and upstream timing are wrong. Configure `$middleware->prepend(new TrustProxyMiddleware(...))` and cookie trust.
- [ ] CSRF: `VerifyCsrfToken` exists in `app/app/Http/Middleware/`. Confirm web forms carry tokens; Inertia SPA pages render via `HandleInertiaRequests` which should include the token — verify it's present on POST routes (login/register forms).
- [ ] SQL injection: codebase uses parameterized queries via Eloquent/Active queries (verify raw `DB::statement` calls pass bound params, not string concat).
- [ ] XSS: Blade `{{ }}` output escaping is on by default — keep it on.

### 10. Hardening artifacts (added this session)
- [ ] **Clarity script loaded** in `app/resources/views/app.blade.php` and `welcome.blade.php` (page visitors start being tracked immediately on ain1.ir).
- [ ] `.env` reflects `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://ain1.ir`.
- [ ] `.gitignore` ignores `.env`/`.env.*`; `.env.example` committed with placeholders.
- [ ] nginx adds HSTS, Referrer-Policy, CSP (allowing `clarity.ms`), Error pages.
- [ ] Migration plan stored; backups tested (Postgres dump + MinIO version retention).

### 11. Reliability & observability
- [ ] `/health` endpoint (HTTP 200 static text) must actually probe DB + Redis; currently it returns static text only. Point it at a tiny Laravel controller that pings DB/Redis and returns 200/503.
- [ ] Log rotation: `config/logging.php` uses single-driver — switch `stack` to `daily` with `'days' => 14` (single file grows unbounded under load).
- [ ] Set `LOG_LEVEL=warning` for production (already set in `.env`); bump handler to daily.
- [ ] Error display off in prod (`APP_DEBUG=false` — verified).

### 12. Test coverage against current state
- [ ] CI workflow `.github/workflows/docker-deploy.yml` runs `composer outdated` security scan **and** tests. Currently it only builds/pushes. Add steps: checkout → `composer audit` (or `composer outdated`) → `composer test` (phpunit/pest) → build & push.
- [ ] Cypress BDD suite (`cypress-bdd/`) exists on disk; wire into CI.
- [ ] Run migrations + full suite locally before first stable release.

### 13. Single source of truth (operational hygiene)
- [ ] Root `composer.json`/`composer.lock`/root `vendor/` (422MB) is a stale duplicate of `app/` — either remove and let `app/` own everything, or remove `app/composer.json` to avoid divergence. Do not `composer install` from repo root.
- [ ] Keep `app/` as the canonical Laravel root; do not develop from repo root without remapping autoload.

---

## P2 — Optimization / polish

- [ ] Profile N+1 queries on dashboard/lead tables; add eager loading.
- [ ] Redis cache TTL tuning on hot reads; verify `CACHE_DRIVER=redis` cache hits.
- [ ] Asset caching: static-asset `expires 1y` + immutable — works (nginx serves `/assets/` directly).
- [ ] PHP-FPM `pm.max_children` headroom for concurrent scan events (currently 5).
- [ ] Sentry / third-party error alerting configured for post-launch (currently `Broadcasting` defaults to `log` — safe, but opt in to telemetry).
- [ ] Document restore procedure and share the team checklist at handoff.

---

## Owner assignment

| Priority | Action | Main actor | See above |
|---|---|---|---|
| P0-1 | HTTPS/TLS handled by ArvanCloud edge — no nginx TLS block | DevOps | §1 |
| P0-2 | Database queue driver + queue worker service | DevOps | §2 |
| P0-3 | Mail temporarily switched to `log` mailer | Fullstack | §3 |
| P0-4 | Rate-limit API write endpoints (top of checklist) | Fullstack | §4 |
| P0-5 | Fix CORS `*` + credentials | Devops | §5 |
| P0-6 | Rotate `APP_KEY` — **user deferred** | User | §6 |
| P1-7 | Migration + cache + asset rebuild on deploy | DevOps | §7 |
| P1-8 | `.dockerignore` + pinned images + remove dev volume mount | DevOps | §8 |
| P1-9 | TrustProxies, CSRF spot-check | Fullstack | §9 |
| P1-10 | Clarity/env/nginx hardening — **completed this session** | — | §10 |
| P1-11 | Probing `/health` + log rotation + LOG_LEVEL | DevOps | §11 |
| P1-12 | CI security + test + cypress | DevOps | §12 |
| P1-13 | Vendor single-source fix | DevOps | §13 |
| P2 | N+1, FPM workers, alerting | Fullstack | §P2 |

---

## Verification run (do this before declaring ready)

```bash
# 1. App boots
curl -k https://ain1.ir/ ; echo "exit=$?"

# 2. Auth flow + core routes respond 2xx
for p in / /login /register /qr /checkin /exhibitors /api/identity/otp/request /api/access/checkin; do
  code=$(curl -s -o /dev/null -w '%{http_code}' -k https://ain1.ir$p)
  echo "$p -> $code"
done

# 3. Health actually probes deps
curl -k https://ain1.ir/health ; echo "exit=$?"

# 4. Clarity tag present in HTML
grep -c 'clarity.ms/tag/z11ojahpug' <(curl -sk https://ain1.ir/)

# 5. HTTPS enforced, no plaintext cert leakage
curl -sI https://ain1.ir | grep -E 'HTTP/2|strict-transport'

# 6. Pipeline guards ship
docker compose -f docker-compose.yml build app ; docker run --rm --entrypoint php signic/app php artisan test --no-coverage
```

All green → deploy and monitor for the first traffic window.
