##########################
# Stage 1 : Builder
##########################
FROM php:8.2-apache AS builder

# Build arg pour composer conditionnel
ARG APP_ENV=dev

# Apache
RUN a2enmod rewrite

# Dépendances système
RUN apt-get update && apt-get install -y --no-install-recommends \
    libzip-dev zip unzip git curl gnupg2 ca-certificates libssl-dev pkg-config \
    libjpeg62-turbo-dev libpng-dev libfreetype6-dev \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j"$(nproc)" gd \
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

# Dépendances PHP
COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --no-scripts

# Code source
COPY . .

##########################
# Stage 2 : Image finale
##########################
FROM php:8.2-apache

RUN a2enmod rewrite

RUN apt-get update && apt-get install -y --no-install-recommends \
    libzip-dev \
    libjpeg62-turbo-dev libpng-dev libfreetype6-dev \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j"$(nproc)" gd \
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

COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN sed -i 's/\r$//' /usr/local/bin/entrypoint.sh \
    && chmod +x /usr/local/bin/entrypoint.sh
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
