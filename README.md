# SupportFlow AI

**Status: partial implementation / local development foundation. This is not the completed product described by the implementation plan.**

The full original [supportflow-ai-laravel-saas-implementation-plan.md](supportflow-ai-laravel-saas-implementation-plan.md) is preserved in this project root. See [IMPLEMENTATION_STATUS.md](IMPLEMENTATION_STATUS.md) for implemented behavior, omissions and verification limits.

## Independent application

- PHP 8.3+, Laravel 13; Vue 3, Inertia 2, TypeScript, Tailwind 3, Vite 8; Node 24 recommended.
- Own Composer/npm manifests, lockfiles, installed vendor/node_modules, APP_KEY, cookie name, storage, configuration and migrations. No sibling app or dependency-directory symlinks.
- MySQL database: `supportflow_ai`; local URL: `http://localhost:8205`.
- `.env` uses database username `izahar` and password `password` as requested. `.env` is ignored by Git and is mode 0600. Configure real deployment credentials outside Git.
- ERP uses additional dedicated tenant databases. Other applications scope records using `workspace_id` (the implementation name for the plan's tenant/organization boundary).

## Local setup

The current sandbox cannot connect to MySQL or Redis. No successful migration/seed/application-database check is claimed.

On a host with MySQL running, the configured user must have CREATE privileges for this application's database:

```bash
./scripts/setup.sh
php artisan serve --host=127.0.0.1 --port=8205
# separate terminal for database-backed jobs
php artisan queue:work --tries=3
```

Setup installs locked dependencies, creates only the configured database, runs migrations and catalog seeders, checks TypeScript and builds frontend assets. It never drops a database. The seeder creates a development plan; it does not create an administrator or guess a password. Register through `/register`; with `MAIL_MAILER=log`, follow the verification URL in `storage/logs/laravel.log`. Create a workspace at `/dashboard`. Existing registered accounts can be added by a workspace owner through Team.

Roles currently implemented: owner, admin, editor and viewer. These are a foundation, not the full domain role matrices in the plan. Platform-admin directory access never grants workspace membership. Platform admin can be assigned through an explicitly authorized console/database operation; there is no public privilege-grant route.

## Containers

A development Dockerfile and Compose definition are supplied, **not executed/verified here**:

```bash
export MYSQL_ROOT_PASSWORD='choose-a-local-root-password'
docker compose up --build -d
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed --force
```

Compose creates an independent MySQL volume and Redis service. The application currently uses file cache/session and database queues; Redis/Horizon/Reverb integration is outstanding. The built-in PHP server is for local development, not a production deployment topology.

## Verification

```bash
composer check-platform-reqs
npm run typecheck
npm run build
vendor/bin/phpunit tests/Unit tests/Feature/SmokeTest.php
# Create a separate disposable test database, then run DB-dependent tests:
DB_DATABASE=supportflow_ai_test php scripts/create-database.php
vendor/bin/phpunit
```

Tests reject a MySQL database name without the `_test` suffix. Database feature tests use RefreshDatabase and must never target application data. CI defines a disposable MySQL service. CI execution, browser E2E, full MySQL tests, provider sandbox tests and load/recovery tests remain unverified.

## Feature entry points

`/` marketing; `/register` and `/login`; `/dashboard` workspace selection; `/app/{slug}/dashboard`; module lists/detail/actions; `/app/{slug}/team`; `/app/{slug}/billing`; `/super-admin` metadata directory.

Contact, conversation, ticket, knowledge article and assistant records; local replies and internal notes with idempotency; knowledge publication; close/reopen/human-handoff operations; handoff disables automation. Messages are stored locally, not delivered to a visitor or external channel.

Paid checkout is deliberately disabled until signed webhook reconciliation and Stripe sandbox tests are implemented. Installed SDKs alone do not constitute an integration. No live provider credentials, payment activation, AI output or GPS data are simulated.
