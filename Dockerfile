##########################
# Stage 1 : Builder
##########################
FROM php:8.3-fpm AS builder

ARG APP_ENV=dev

# Dépendances système + extensions PHP nécessaires au build
RUN apt-get update && apt-get install -y --no-install-recommends \
    libzip-dev zip unzip git curl gnupg2 ca-certificates \
    libssl-dev pkg-config \
    libjpeg62-turbo-dev libpng-dev libfreetype6-dev \
    libxml2-dev \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j"$(nproc)" gd zip pdo pdo_mysql dom xml simplexml \
 && pecl install mongodb && docker-php-ext-enable mongodb \
 && rm -rf /var/lib/apt/lists/*

# Node.js + Yarn (build assets en dev/CI)
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
 && apt-get install -y nodejs \
 && npm install -g yarn

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# wait-for-it
COPY wait-for-it.sh /wait-for-it.sh
RUN chmod +x /wait-for-it.sh

WORKDIR /var/www/Cinephoria_web

# Dépendances PHP (pas d'auto-scripts pendant le build)
COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --no-scripts

# Code source
COPY . .

# Build des assets (ajouté)
RUN yarn install && yarn build

##########################
# Stage 2 : Image finale
##########################
FROM php:8.3-fpm

# Libs runtime + extensions PHP
RUN apt-get update && apt-get install -y --no-install-recommends \
    libzip-dev \
    libssl-dev pkg-config \
    libjpeg62-turbo-dev libpng-dev libfreetype6-dev \
    libxml2-dev \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j"$(nproc)" gd zip pdo pdo_mysql dom xml simplexml \
 && pecl install mongodb && docker-php-ext-enable mongodb \
 && rm -rf /var/lib/apt/lists/*

# Node + Yarn (utile seulement si build d'assets au runtime en DEV)
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
 && apt-get install -y nodejs \
 && npm install -g yarn

WORKDIR /var/www/Cinephoria_web

# Copier depuis le builder (vendor + code)
COPY --from=builder /var/www/Cinephoria_web /var/www/Cinephoria_web
COPY --from=builder /usr/bin/composer /usr/bin/composer

# wait-for-it
COPY wait-for-it.sh /wait-for-it.sh
RUN chmod +x /wait-for-it.sh

# Entrypoint
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN sed -i 's/\r$//' /usr/local/bin/entrypoint.sh \
 && chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
