FROM node:24-bookworm-slim AS frontend
WORKDIR /build
COPY package*.json ./
RUN npm ci --ignore-scripts
COPY resources ./resources
COPY vite.config.js tailwind.config.js postcss.config.js tsconfig.json ./
# Ziggy is resolved from Composer's package in the PHP build stage instead.
FROM php:8.3-cli-bookworm AS app
RUN apt-get update && apt-get install -y git unzip libzip-dev libicu-dev libonig-dev libxml2-dev && docker-php-ext-install pdo_mysql bcmath intl mbstring pcntl zip dom && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY --from=frontend /usr/local/bin/node /usr/local/bin/node
COPY --from=frontend /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm
WORKDIR /app
COPY . .
RUN composer install --no-interaction --prefer-dist --no-scripts && composer dump-autoload --no-scripts && php artisan package:discover
COPY --from=frontend /build/node_modules ./node_modules
RUN npm run typecheck && npm run build
EXPOSE 8000
CMD ["php","artisan","serve","--host=0.0.0.0","--port=8000","--no-reload"]
