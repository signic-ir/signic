# Production QA Checklist

## Pre-Deployment Verification

- [ ] All `.env` values are properly configured for production (not using defaults)
- [ ] `APP_ENV=production` and `APP_DEBUG=false`
- [ ] SSL certificates are valid and properly configured
- [ ] Database migrations are up-to-date (`php artisan migrate --force`)
- [ ] All queue workers are stopped during deployment
- [ ] Cache is cleared (`php artisan config:cache`, `php artisan route:cache`, `php artisan view:cache`)
- [ ] Production dependencies installed (`composer install --no-dev --optimize-autoloader`)

## Infrastructure Checks

- [ ] PostgreSQL database is running and accessible
- [ ] Redis is running and accessible for caching/sessions
- [ ] MinIO/S3 is configured and accessible for file storage
- [ ] Mailpit (or production email service) is configured
- [ ] Firewall rules allow only necessary ports (80, 443)
- [ ] Docker containers are using production build stage
- [ ] Health check endpoints are functional

## Security Audit

- [ ] `.env` file is not accessible via web
- [ ] `APP_KEY` is set to a secure random value
- [ ] All external dependencies are up-to-date (`composer outdated`)
- [ ] HTTPS is enforced (redirect HTTP to HTTPS)
- [ ] SQL injection prevention verified (parameterized queries)
- [ ] XSS protection enabled ( Blade `{{ }}` escaping)
- [ ] CSRF protection is active on forms
- [ ] Rate limiting is configured on sensitive endpoints
- [ ] Sensitive data (API keys, secrets) are not logged
- [ ] Directory listing is disabled

## Application Functionality

- [ ] Main application loads without errors
- [ ] All module providers are registered correctly
- [ ] Database connections work (test with a simple query)
- [ ] Authentication flow works (login/logout/password reset)
- [ ] File uploads work (MinIO/S3 integration)
- [ ] Queue jobs process correctly
- [ ] Email sending works
- [ ] Horizon dashboard is accessible (if used)

## Module-Specific Tests

### AccessControl Module
- [ ] Turnstile check-in/check-out endpoints respond correctly
- [ ] Rate limiting on scan endpoints works
- [ ] Events are dispatched correctly
- [ ] Scan event logging works

### Exhibition Module
- [ ] Exhibitor registration flow works
- [ ] Lead capture functionality works
- [ ] Booth management functions correctly
- [ ] Lead tagging system works

### Identity Module
- [ ] User registration works
- [ ] Role/permission assignments work (Spatie)
- [ ] Authentication guards work correctly

### Registration Module
- [ ] Event registration flow works
- [ ] Payment integration works (if applicable)
- [ ] Confirmation emails send

## Performance Checks

- [ ] Application responds within acceptable time (< 2s average)
- [ ] Database queries are optimized (no N+1 queries)
- [ ] Caching is working (check Redis cache hits)
- [ ] Assets are properly cached (CSS/JS)
- [ ] Laravel Horizon queue processing is healthy
- [ ] Memory usage is stable (no leaks)

## Monitoring & Logging

- [ ] Error logging is working (check `storage/logs`)
- [ ] Application metrics are being collected
- [ ] Health check endpoint (`/health`) returns correct status
- [ ] Log rotation is configured
- [ ] Alerting is configured for critical errors

## Backup & Recovery

- [ ] Database backups are configured and tested
- [ ] File storage (MinIO) backups are configured
- [ ] Recovery procedures are documented
- [ ] Recent backup can be restored successfully

## Final Deployment Steps

- [ ] Deployment is performed during maintenance window
- [ ] All team members are notified
- [ ] Rollback plan is ready
- [ ] Post-deployment smoke tests pass
- [ ] Monitoring dashboards show normal operation