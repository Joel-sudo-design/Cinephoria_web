# ==================================
# Stage 1: Builder pour les dépendances PHP
# ==================================
FROM dunglas/frankenphp:php8.4 AS builder

# Installer les dépendances système pour compiler les extensions PHP
RUN apt-get update && \
    apt-get install -y --no-install-recommends \
        libzip-dev \
        libssl-dev \
        pkg-config \
        git \
        unzip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Installer les extensions PHP nécessaires
RUN install-php-extensions \
    zip \
    pdo_mysql \
    mongodb \
    opcache \
    apcu \
    intl

# Copier Composer depuis l'image officielle
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copier les fichiers de dépendances en premier pour optimiser le cache Docker
COPY composer.json composer.lock symfony.lock ./

# Installer les dépendances PHP optimisées pour production
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --classmap-authoritative \
    && composer clear-cache

# Copier le code source après les dépendances pour meilleur cache
COPY . .

# Optimiser l'autoloader de Composer
RUN composer dump-autoload --optimize --classmap-authoritative --no-dev


# ==================================
# Stage 2: Image de production finale
# ==================================
FROM dunglas/frankenphp:php8.4

# Installer uniquement les dépendances runtime nécessaires
RUN apt-get update && \
    apt-get install -y --no-install-recommends \
        libzip5 \
        mariadb-client \
        curl \
        ca-certificates \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Installer les extensions PHP
RUN install-php-extensions \
    zip \
    pdo_mysql \
    mongodb \
    opcache \
    apcu \
    intl

# Configuration OPcache optimisée pour 8GB RAM + JIT
RUN { \
    echo 'opcache.enable=1'; \
    echo 'opcache.enable_cli=1'; \
    echo 'opcache.memory_consumption=256'; \
    echo 'opcache.interned_strings_buffer=32'; \
    echo 'opcache.max_accelerated_files=20000'; \
    echo 'opcache.validate_timestamps=0'; \
    echo 'opcache.revalidate_freq=0'; \
    echo 'opcache.save_comments=0'; \
    echo 'opcache.fast_shutdown=1'; \
    echo 'opcache.enable_file_override=1'; \
    echo 'opcache.max_wasted_percentage=10'; \
    echo 'opcache.jit=tracing'; \
    echo 'opcache.jit_buffer_size=128M'; \
    echo 'realpath_cache_size=4096K'; \
    echo 'realpath_cache_ttl=600'; \
    } > /usr/local/etc/php/conf.d/opcache.ini

# Configuration APCu pour 8GB RAM
RUN { \
    echo 'apc.enabled=1'; \
    echo 'apc.shm_size=512M'; \
    echo 'apc.ttl=7200'; \
    echo 'apc.enable_cli=1'; \
    echo 'apc.gc_ttl=3600'; \
    echo 'apc.entries_hint=4096'; \
    } > /usr/local/etc/php/conf.d/apcu.ini

# Paramètres PHP de production
RUN { \
    echo 'memory_limit=512M'; \
    echo 'max_execution_time=60'; \
    echo 'max_input_time=60'; \
    echo 'post_max_size=50M'; \
    echo 'upload_max_filesize=50M'; \
    echo 'expose_php=Off'; \
    echo 'display_errors=Off'; \
    echo 'display_startup_errors=Off'; \
    echo 'log_errors=On'; \
    echo 'error_log=/app/var/log/php_errors.log'; \
    echo 'error_reporting=E_ALL & ~E_DEPRECATED & ~E_STRICT'; \
    echo 'max_input_vars=5000'; \
    echo 'date.timezone=Europe/Paris'; \
    } > /usr/local/etc/php/conf.d/php-prod.ini

# Optimisations session
RUN { \
    echo 'session.save_handler=files'; \
    echo 'session.save_path=/app/var/sessions'; \
    echo 'session.gc_probability=1'; \
    echo 'session.gc_divisor=1000'; \
    echo 'session.gc_maxlifetime=3600'; \
    echo 'session.cookie_httponly=1'; \
    echo 'session.cookie_secure=1'; \
    echo 'session.use_strict_mode=1'; \
    } > /usr/local/etc/php/conf.d/session.ini

WORKDIR /app

# Copier vendor depuis le builder
COPY --from=builder /app/vendor ./vendor

# Copier le code source
COPY . .

# Créer les répertoires nécessaires
RUN mkdir -p \
    var/cache/prod \
    var/log \
    var/sessions \
    public/image_film \
 && chmod -R 755 var \
 && chmod -R 755 public/image_film

# Copier entrypoint et Caddyfile
COPY --chmod=755 entrypoint.sh /entrypoint.sh
COPY Caddyfile /etc/caddy/Caddyfile

EXPOSE 80 443

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=60s --retries=3 \
    CMD curl -f -A "HealthCheck" http://localhost/health || exit 1

ENTRYPOINT ["/entrypoint.sh"]
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]