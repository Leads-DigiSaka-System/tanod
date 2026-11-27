FROM node:22-bookworm-slim AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY . .
ENV VITE_WEBSOCKET_APP_KEY=tanod-staging VITE_WEBSOCKET_HOST=tanod-staging.invalid VITE_WEBSOCKET_PORT=443
RUN npm run build \
    && npm pack leaflet@1.9.4 --pack-destination /tmp --silent \
    && mkdir -p public/staging \
    && tar -xzf /tmp/leaflet-1.9.4.tgz --strip-components=2 -C public/staging package/dist/leaflet.js package/dist/leaflet.css package/dist/images \
    && tar -xOzf /tmp/leaflet-1.9.4.tgz package/LICENSE > public/staging/leaflet-license.txt \
    && cp staging/maps.js public/staging/maps.js

FROM composer:2 AS composer_binary
FROM php:8.4-cli-bookworm
WORKDIR /app
RUN apt-get update \
    && apt-get install -y --no-install-recommends libpng-dev libzip-dev unzip \
    && docker-php-ext-install -j2 bcmath gd zip \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer_binary /usr/bin/composer /usr/bin/composer
COPY . .
RUN composer install --no-dev --no-interaction --no-progress --prefer-dist --optimize-autoloader
COPY --from=assets /app/public/build public/build
COPY --from=assets /app/public/staging public/staging
ENV PORT=10000
EXPOSE 10000
CMD ["sh", "staging/start.sh"]
