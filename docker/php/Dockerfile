FROM php:8.4-apache

# ─────────────────────────────────────────────
# System dependencies
# ─────────────────────────────────────────────
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    curl \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

# ─────────────────────────────────────────────
# Apache modules
# ─────────────────────────────────────────────
RUN a2enmod rewrite

# ─────────────────────────────────────────────
# VirtualHost
# ─────────────────────────────────────────────
COPY docker/php/apache.conf /etc/apache2/sites-available/000-default.conf

# ─────────────────────────────────────────────
# Composer
# ─────────────────────────────────────────────
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# ─────────────────────────────────────────────
# Xdebug (installed but DISABLED by default)
# ─────────────────────────────────────────────
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

# ─────────────────────────────────────────────
# PHP config
# ─────────────────────────────────────────────
COPY docker/php/php.ini /usr/local/etc/php/php.ini
COPY docker/php/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

# ─────────────────────────────────────────────
# Workdir
# ─────────────────────────────────────────────
WORKDIR /var/www/php
