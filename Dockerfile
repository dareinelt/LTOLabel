# ---- Abhängigkeiten (Composer) ----
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist --optimize-autoloader --ignore-platform-req=ext-gd

# ---- Laufzeit-Image ----
FROM php:8.3-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
        curl \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libsqlite3-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" gd pdo_sqlite pdo_mysql mbstring fileinfo \
    && rm -rf /var/lib/apt/lists/* \
    && a2enmod rewrite

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
        /etc/apache2/sites-available/*.conf \
        /etc/apache2/apache2.conf \
        /etc/apache2/conf-available/*.conf

# Frontcontroller-Fallback für die hübschen URLs (/labels, /print/...).
COPY .docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html
COPY --from=vendor /app/vendor ./vendor
COPY . .

RUN chown -R www-data:www-data data \
    && find data -type d -exec chmod 775 {} \; \
    && find data -type f -exec chmod 664 {} \;

HEALTHCHECK --interval=30s --timeout=5s --start-period=15s --retries=3 \
    CMD curl -fsS http://127.0.0.1/ -o /dev/null

EXPOSE 80
