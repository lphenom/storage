FROM php:8.1-alpine

# Install system dependencies
RUN apk add --no-cache \
    git \
    unzip \
    libzip-dev \
    && docker-php-ext-install zip

# Install Composer (pinned version)
COPY --from=composer:2.9.5 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copy composer files first for layer caching
COPY composer.json ./

# Install dependencies
RUN composer install --no-scripts --no-progress --prefer-dist

# Copy the rest of the project
COPY . .

