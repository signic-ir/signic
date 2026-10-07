# Signic - Event Registration & Lead Capture Platform

A modular monolith built with Laravel 13, PostgreSQL 16, and Redis.

## Architecture

```
app/
├── Modules/
│   ├── Identity/          # OTP authentication via SMS
│   ├── Registration/      # Attendee registration & QR badge tokens
│   ├── AccessControl/     # Turnstile check-in/out with Redis atomic locks
│   ├── Exhibition/        # Exhibitor lead capture & management
│   └── Shared/            # Shared utilities & contracts
```

## Prerequisites

- Docker 24+
- Docker Compose v2+

## Quick Start

```bash
# 1. Clone & navigate
cd signic

# 2. Copy environment file
cp .env.example .env

# 3. Start infrastructure
docker compose up -d

# 4. Install PHP dependencies (inside container)
docker compose exec app composer install

# 5. Generate app key
docker compose exec app php artisan key:generate

# 6. Run migrations
docker compose exec app php artisan migrate

# 7. Start Horizon (queue workers)
docker compose exec app php artisan horizon
```

## Service URLs

| Service | URL |
|---------|-----|
| Laravel App | http://localhost:8000 |
| PostgreSQL | localhost:5432 |
| Redis | localhost:6379 |
| Horizon Dashboard | http://localhost:5801 |
| MinIO Console | http://localhost:9001 |
| Mailpit UI | http://localhost:11434 |

## Environment Variables

Copy `.env.example` to `.env` and adjust:

```bash
# Application
APP_NAME=Signic
APP_ENV=development
APP_KEY=base64:...
APP_DEBUG=true

# Database (PostgreSQL 16)
DB_CONNECTION=postgres
DB_HOST=db
DB_PORT=5432
DB_DATABASE=signic
DB_USERNAME=postgres
DB_PASSWORD=postgres

# Redis
REDIS_HOST=redis
REDIS_PORT=6379
REDIS_DATABASE=0

# MinIO (S3-compatible)
MINIO_ENDPOINT=minio:9000
MINIO_BUCKET=signic-uploads

# Mailpit
MAILPIT_HOST=mailpit
MAILPIT_PORT=11434
```

## Docker Compose Commands

```bash
# Start all services
docker compose up -d

# View logs
docker compose logs -f app

# Stop all services
docker compose down

# Stop and remove volumes
docker compose down -v

# Restart single service
docker compose restart app

# Execute commands in container
docker compose exec app php artisan migrate
docker compose exec app php artisan test
docker compose exec app php artisan horizon
```

## Testing

```bash
# Run tests
docker compose exec app php artisan test

# Run with coverage
docker compose exec app php artisan test --coverage
```

## Queue Workers

Horizon runs as a separate service for production. For local development:

```bash
# Start queue worker
docker compose exec app php artisan queue:work

# Or use Horizon
docker compose exec app php artisan horizon
```

## Module Structure

Each module follows Laravel conventions:

```
Modules/{ModuleName}/
├── Contracts/           # Service interfaces (for cross-module comms)
├── Providers/           # Service provider
├── Services/            # Business logic
├── Http/
│   ├── Controllers/     # API controllers
│   ├── Requests/        # Form requests
│   └── Resources/       # API resources
├── Routes/
│   ├── web.php          # Web routes
│   └── api.php          # API routes
├── Database/
│   ├── Migrations/
│   └── Seeders/
└── Resources/
    ├── Views/
    └── Lang/
```

## Cross-Module Communication

Modules communicate via:
1. **Service Contracts** - Interfaces registered in service providers
2. **Domain Events** - Laravel events for async communication
3. **Never** - Direct database access or model imports across modules

## Development

```bash
# Enter app container
docker compose exec app bash

# Run linting
docker compose exec app php artisan lint

# Clear caches
docker compose exec app php artisan optimize:clear

# Generate IDE helpers
docker compose exec app php artisan ide-helper:generate
```

## Production Deployment

1. Build production image:
```bash
docker compose -f docker-compose.yml -f docker-compose.prod.yml build
```

2. Set production environment variables:
```bash
APP_ENV=production
APP_DEBUG=false
```

3. Run migrations:
```bash
docker compose exec app php artisan migrate --force
```

4. Optimize:
```bash
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache
```

## Security Notes

- No secrets in repository or images
- Use Docker secrets or env files for production
- OTP & scan endpoints are rate-limited
- QR tokens are cryptographically secure & server-validated
- Turnstile operations use Redis atomic locks for race-condition safety

## License

Proprietary - Signic Team