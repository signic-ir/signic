# QA Checklist: Resolve 500 Internal Server Error on ain1.ir 

## Immediate Diagnosis Steps

### 1. Web Server Health Checks

- [ ] Check if nginx is running on the production server
- [ ] Check if all Docker containers are up (`docker ps -a`)
- [ ] Check container logs for PHP/PHP-FPM errors
- [ ] Check nginx upstream configuration (`nginx.conf` or virtual host config)
- [ ] Verify port 80/443 are open and listening on production server

### 2. Laravel Application Checks

- [ ] Check the Laravel application is accessible inside its container
- [ ] Check if the app is running on port 8000 or configured FastCGI port
- [ ] Verify `APP_DEBUG=false` in production (do not expose errors)
- [ ] Check `laravel.log` inside the container after triggering a request

### 3. Database & Infrastructure Checks

- [ ] Check if PostgreSQL is running and responding
- [ ] Verify database connection settings in `.env`
- [ ] Check if Redis is running and accessible
- [ ] Verify MinIO is running and accessible
- [ ] Check if the Laravel queue worker needs to be started
- [ ] Verify `.env` file has correct production values (not dev)

### 4. PHP/Environment Issues

- [ ] Check PHP version matches requirements
- [ ] Check for missing PHP extensions (`composer diagnose`, `php -m`)
- [ ] Verify `APP_KEY` is properly set
- [ ] Check for syntax errors in recently deployed code
- [ ] Verify Composer dependencies are installed

### 5. Cache/Configuration

- [ ] Clear application cache if recently deployed
- [ ] Run `php artisan config:clear`, `route:clear`, `cache:clear`
- [ ] Check if storage/bootstrap/cache directories have correct permissions
- [ ] Check `storage/logs/laravel.log` for stack traces

## Known Issues from Local Log Analysis

The nginx error log showed:
- `connect() failed (111: Connection refused) while connecting to upstream` - Laravel app container not running on the expected upstream IP/port
- `upstream sent unsupported FastCGI protocol version: 72` - PHP-FPM protocol version mismatch (often PHP 8.x with nginx FastCGI settings)

### Fix Priority

1. Ensure the Laravel (PHP-FPM) container is running and listening on the configured port
2. Verify nginx FastCGI settings (`fastcgi_pass`, `fastcgi_param SCRIPT_FILENAME`)
3. Check PHP-FPM is configured correctly and listening on port 9000 or configured port
4. Verify `.env` values are correct and app key is set
5. Run migrations and verify database connection

## Deployment & Monitoring

- [ ] Monitor `storage/logs/laravel.log` after restart
- [ ] Check nginx error log for new entries
- [ ] Verify application responds on ain1.ir
- [ ] Test all main endpoints (home, login, register, dashboards)
- [ ] Ensure queue workers are running if Horizon is used
- [ ] Set up proper error logging (Sentry, Logflare, etc.) for production
- [ ] Create automated health check endpoint (`/health`)
