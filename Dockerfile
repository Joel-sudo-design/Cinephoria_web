##########################
# Stage 1 : Builder
##########################
FROM php:8.4-fpm AS builder

ARG APP_ENV=dev

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

# wait-for-it
COPY wait-for-it.sh /wait-for-it.sh
RUN chmod +x /wait-for-it.sh

WORKDIR /var/www/Cinephoria_web

# Dépendances PHP (DEV : on garde les paquets dev, pas d'auto-scripts)
COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --no-scripts

# Dépendances Node.js
COPY package.json yarn.lock* package-lock.json* ./
RUN if [ -f yarn.lock ]; then yarn install --frozen-lockfile; elif [ -f package-lock.json ]; then npm ci; else npm install; fi

# Code source
COPY . .

# Build des assets
RUN if [ -f yarn.lock ]; then yarn build || true; else npm run build || true; fi

##########################
# Stage 2 : Image finale
##########################
FROM php:8.4-fpm

WORKDIR /var/www/Cinephoria_web

# Dépendances système + Node.js
RUN apt-get update && apt-get install -y --no-install-recommends \
    libzip-dev \
    libjpeg62-turbo-dev libpng-dev libfreetype6-dev \
    curl gnupg2 ca-certificates \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j"$(nproc)" gd \
 && curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
 && apt-get install -y nodejs \
 && npm install -g yarn \
 && rm -rf /var/lib/apt/lists/*

# Extensions PHP
RUN docker-php-ext-install zip pdo pdo_mysql
RUN pecl install mongodb && docker-php-ext-enable mongodb

# OPcache
RUN { \
  echo "opcache.enable=1"; \
  echo "opcache.enable_cli=1"; \
  echo "opcache.jit=1255"; \
  echo "opcache.jit_buffer_size=64M"; \
  echo "opcache.memory_consumption=256"; \
  echo "opcache.max_accelerated_files=20000"; \
} > /usr/local/etc/php/conf.d/opcache.ini

# Copier Composer
COPY --from=builder /usr/bin/composer /usr/bin/composer

# Copier l'application depuis le builder
COPY --from=builder /var/www/Cinephoria_web /var/www/Cinephoria_web

# Copier explicitement node_modules pour s'assurer qu'ils sont présents
COPY --from=builder /var/www/Cinephoria_web/node_modules ./node_modules

# wait-for-it
COPY wait-for-it.sh /wait-for-it.sh
RUN sed -i 's/\r$//' /wait-for-it.sh && \
    chmod +x /wait-for-it.sh

EXPOSE 9000

COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN sed -i 's/\r$//' /usr/local/bin/entrypoint.sh \
    && chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]