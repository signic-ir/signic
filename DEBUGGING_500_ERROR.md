# Debugging Checklist - 500 Internal Server Error on ain1.ir

## Symptom
- HTTP 500 Internal Server Error returned
- No Laravel logs generated

## Step 1: Check Laravel Logs
```bash
ls -la /home/hamid/signic/storage/logs/
tail -n 100 /home/hamid/signic/storage/logs/laravel.log
# Check if app is using a custom log channel
grep -n "log" /home/hamid/signic/config/logging.php
```
**Why no log**: Common causes - error logging disabled, wrong log channel, log file not writable, application crashed before Laravel bootstrap.

## Step 2: Check Web Server Logs
```bash
# Nginx (if applicable)
tail -n 50 /var/log/nginx/error.log
tail -n 50 /home/hamid/signic/nginx/error.log

# Check request came to the right site
nginx -T | grep "ain1.ir\|server_name"
```

## Step 3: Verify Docker Containers
```bash
docker compose ps
# Inspect app container logs
docker compose logs app --tail=100
# Check if PHP-FPM container is running
docker compose exec app php artisan --version
```

## Step 4: Check Laravel Application State
```bash
docker compose exec app php artisan config:clear
docker compose exec app php artisan cache:clear
docker compose exec app php artisan route:clear
# Test artisan commands work
docker compose exec app php artisan --version
```

## Step 5: Verify Environment Configuration
```bash
# Check .env values
grep -E "APP_ENV|APP_DEBUG|DB_" /home/hamid/signic/.env
# Ensure .env is loaded
docker compose exec app cat .env | grep APP_DEBUG
```

## Step 6: Check for PHP Errors
```bash
docker compose exec app php -l /home/hamid/signic/bootstrap/app.php
# Check PHP version compatibility
docker compose exec app php --version
```

## Step 7: Check Database Connection
```bash
docker compose exec app php artisan tinker --execute="DB::connection()->getPdo();"
docker compose exec app php artisan migrate:status
```

## Step 8: Check Storage Permissions
```bash
ls -ld /home/hamid/signic/storage
ls -ld /home/hamid/signic/storage/logs
docker compose exec app whoami
```

## Step 9: Enable Debug Mode Temporarily
```bash
# Only for debugging - set APP_DEBUG=true temporarily in .env
# This will show detailed error in browser
grep "APP_DEBUG" /home/hamid/signic/.env
# If using Docker, edit .env and restart
docker compose restart app
```

## Step 10: Check PHP Error Log
```bash
# Check PHP-FPM error log
tail -n 50 /var/log/php-fpm/error.log
# Or check the container
docker compose exec app tail -f /tmp/php_error_log  # adjust path
```

## Step 11: Check Queue Workers / Horizon
```bash
docker compose exec app php artisan horizon:status
# Stop horizon during deployment
docker compose exec app php artisan horizon:stop
```

## Step 12: Check .env File Loading
```bash
docker compose exec app sh -c 'env | grep APP_'
# Check if env file is correctly referenced in docker-compose.yml
grep -n "env_file\|\.env" /home/hamid/signic/docker-compose.yml
```

## Step 13: Check Composer Dependencies
```bash
docker compose exec app composer install --no-dev --optimize-autoloader
docker compose exec app php artisan optimize:clear
```

## Step 14: Check SSL/HTTPS Configuration
- If ain1.ir uses HTTPS, check SSL certificates
- HTTP 500 can occur if redirect loop or SSL misconfiguration
- Check nginx config for SSL settings

## Step 15: Check PHP Memory and Resource Limits
```bash
docker compose exec app php -i | grep -E "memory_limit|max_execution_time"
```

## Step 16: Enable Detailed Error Reporting
Add to bootstrap/app.php temporarily or check:
```php
// In development, Laravel logs errors automatically
// Check app-level exceptions
```

## Step 17: Check for Cached Config/Route Issues
```bash
docker compose exec app rm -rf bootstrap/cache/*.php
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
```

## Step 18: Check Nginx Configuration
```bash
# Verify nginx listens on correct port and passes requests to PHP-FPM
docker compose exec app nginx -t
docker compose exec app cat /etc/nginx/conf.d/default.conf 2>/dev/null
```

## Step 19: Verify PHP-FPM Configuration
```bash
docker compose exec app cat /etc/php/8.*/fpm/pool.d/www.conf 2>/dev/null
# Check PHP-FPM socket permissions
docker compose exec app ls -la /run/php/php-fpm.sock 2>/dev/null
```

## Step 20: Check PHP Extensions
```bash
docker compose exec app php -m
# Ensure required extensions are loaded (pdo_pgsql, redis, etc.)
```

## Common Causes Summary

| Cause | Check | Fix |
|-------|-------|-----|
| No log generated | storage/logs not writable | Fix permissions: `chmod -R 775 storage` |
| Crash before bootstrap | PHP fatal error | Check PHP error log |
| Config cache issue | old cached config | `php artisan config:clear` |
| DB connection fails | DB host unreachable | Check DB credentials |
| PHP-FPM not running | docker compose ps | `docker compose restart app` |
| Nginx misconfigured | nginx error log | Fix proxy_pass to PHP-FPM |
| Missing dependency | composer install | `composer install --no-dev` |
| .env not loaded | env vars check | Verify env_file in compose |

## Next Steps After Finding
1. Fix the root cause found in the logs
2. Clear caches: `php artisan optimize:clear`
3. Restart containers: `docker compose restart app`
4. Test with APP_DEBUG=true temporarily
5. Disable debug mode after fix: `APP_DEBUG=false`
