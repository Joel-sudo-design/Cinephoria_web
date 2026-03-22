FROM dunglas/frankenphp:php8.4

# ==================================
# Étape 1 : Dépendances système
# ==================================
RUN apt-get update && \
    apt-get install -y --no-install-recommends \
        -o Acquire::Retries=3 \
        -o Acquire::http::Timeout=10 \
        libzip-dev \
        libssl-dev \
        pkg-config \
        git \
        unzip \
        curl \
        mariadb-client \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# ==================================
# Étape 2 : Extensions PHP
# ==================================
RUN install-php-extensions \
    zip \
    pdo_mysql \
    mongodb \
    xdebug

# ==================================
# Étape 3 : Node.js 20 LTS et Yarn
# ==================================
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get update \
    && apt-get install -y --no-install-recommends \
        -o Acquire::Retries=3 \
        nodejs \
    && npm install -g yarn \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# ==================================
# Étape 4 : Configuration Xdebug
# ==================================
RUN { \
    echo 'xdebug.mode=debug'; \
    echo 'xdebug.client_host=host.docker.internal'; \
    echo 'xdebug.start_with_request=yes'; \
    } > /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

# ==================================
# Étape 5 : Composer
# ==================================
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# ==================================
# Étape 6 : Script de démarrage
# ==================================
COPY docker/dev/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Répertoire de travail
WORKDIR /app

# Port
EXPOSE 8000

# Point d'entrée qui installe les dépendances et build les assets
ENTRYPOINT ["/entrypoint.sh"]

# Commande par défaut
CMD ["frankenphp", "php-server", "--listen", ":8000", "--root", "/app/public", "--watch"]