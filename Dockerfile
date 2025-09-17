##########################
# Stage 1 : Builder
##########################
FROM php:8.2-apache AS builder

# Build arg pour composer conditionnel
ARG APP_ENV=prod

# Apache
RUN a2enmod rewrite

# Dépendances système
RUN apt-get update && apt-get install -y \
    libzip-dev zip unzip git curl gnupg2 ca-certificates libssl-dev pkg-config \
    && rm -rf /var/lib/apt/lists/*

# Extensions PHP
RUN docker-php-ext-install zip pdo pdo_mysql
RUN pecl install mongodb && docker-php-ext-enable mongodb

# Node.js + Yarn
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs \
    && npm install -g yarn

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# VHost + wait-for-it
COPY 000-default.conf /etc/apache2/sites-available/000-default.conf
COPY wait-for-it.sh /wait-for-it.sh
RUN chmod +x /wait-for-it.sh

WORKDIR /var/www/Cinephoria_web

# Dépendances PHP (conditionnelles)
COPY composer.json composer.lock ./
RUN if [ "$APP_ENV" = "prod" ]; then \
      composer install --no-dev --optimize-autoloader --no-scripts; \
    else \
      composer install --no-interaction --prefer-dist --no-scripts; \
    fi

# Code source
COPY . .

##########################
# Stage 2 : Image finale
##########################
FROM php:8.2-apache

ARG APP_ENV=prod

RUN a2enmod rewrite

RUN apt-get update && apt-get install -y \
    libzip-dev libssl-dev pkg-config \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install zip pdo pdo_mysql
RUN pecl install mongodb && docker-php-ext-enable mongodb

# Node + Yarn (build des assets au runtime via entrypoint)
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs \
    && npm install -g yarn

# Copier depuis le builder
COPY --from=builder /var/www/Cinephoria_web /var/www/Cinephoria_web
COPY --from=builder /usr/bin/composer /usr/bin/composer

# VHost + wait-for-it
COPY 000-default.conf /etc/apache2/sites-available/000-default.conf
COPY wait-for-it.sh /wait-for-it.sh
RUN chmod +x /wait-for-it.sh

WORKDIR /var/www/Cinephoria_web
EXPOSE 80

ENTRYPOINT ["/var/www/Cinephoria_web/entrypoint.sh"]
