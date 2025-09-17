##########################
# Stage 1 : Builder
##########################
FROM php:8.2-apache AS builder

# Activer mod_rewrite
RUN a2enmod rewrite

# Installer les dépendances système et extensions PHP
RUN apt-get update && apt-get install -y \
    libzip-dev zip unzip git curl gnupg2 ca-certificates libssl-dev pkg-config \
    && rm -rf /var/lib/apt/lists/*

# Installer les extensions PHP requises
RUN docker-php-ext-install zip pdo pdo_mysql

# Installer l'extension mongodb via PECL et l'activer
RUN pecl install mongodb && docker-php-ext-enable mongodb

# Installer Node.js et Yarn pour le build des assets
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs \
    && npm install -g yarn

# Installer Composer depuis l'image officielle
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copier la configuration Apache et le script wait-for-it
COPY 000-default.conf /etc/apache2/sites-available/000-default.conf
COPY wait-for-it.sh /wait-for-it.sh
RUN chmod +x /wait-for-it.sh

# Définir le dossier de travail
WORKDIR /var/www/Cinephoria_web

# Copier les fichiers composer et installer les dépendances
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Copier l'ensemble du code source
COPY . .

##########################
# Stage 2 : Image finale
##########################
FROM php:8.2-apache

# Activer mod_rewrite
RUN a2enmod rewrite

# Installer les dépendances d'exécution
RUN apt-get update && apt-get install -y \
    libzip-dev libssl-dev pkg-config \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install zip pdo pdo_mysql
RUN pecl install mongodb && docker-php-ext-enable mongodb

# Installer Node.js et Yarn pour le build des assets
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs \
    && npm install -g yarn

# Copier Composer et le code compilé depuis le stage builder
COPY --from=builder /var/www/Cinephoria_web /var/www/Cinephoria_web
COPY --from=builder /usr/bin/composer /usr/bin/composer

# Copier la configuration Apache et le script wait-for-it (au cas où ils auraient changé)
COPY 000-default.conf /etc/apache2/sites-available/000-default.conf
COPY wait-for-it.sh /wait-for-it.sh
RUN chmod +x /wait-for-it.sh

# Définir le dossier de travail
WORKDIR /var/www/Cinephoria_web

# Exposer le port HTTP
EXPOSE 80

# Lancer le script d'entrée de l'application
ENTRYPOINT ["/var/www/Cinephoria_web/entrypoint.sh"]
