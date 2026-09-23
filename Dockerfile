# ---- Abhängigkeiten (Composer) ----
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist --optimize-autoloader

# ---- Laufzeit-Image ----
FROM php:8.3-apache

RUN docker-php-ext-install pdo_sqlite pdo_mysql mbstring \
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

EXPOSE 80
