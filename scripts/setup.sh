#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
composer install --no-interaction
if [ ! -f .env ]; then cp .env.example .env; fi
php -r 'require "vendor/autoload.php"; Dotenv\Dotenv::createImmutable(getcwd())->load(); exit(empty($_ENV["APP_KEY"])?1:0);' || php artisan key:generate
php scripts/create-database.php
php artisan migrate --force --no-interaction
php artisan db:seed --force --no-interaction
npm ci --ignore-scripts
npm run typecheck
npm run build
php artisan about
