#!/bin/bash
set -e

echo "Attente du port 3306..."
/wait-for-it.sh db:3306 -t 30

# Composer install si vendor absent ou vide
if [ ! -d vendor ] || [ -z "$(ls -A vendor 2>/dev/null)" ]; then
  echo "vendor/ absent ou vide -> composer install"
  composer install --no-interaction --prefer-dist
fi

echo "Création de la base si besoin"
php bin/console doctrine:database:create --if-not-exists || true

echo "Migration de la base si besoin"
php bin/console doctrine:migrations:migrate --no-interaction || true

echo "Execution des autres scripts"
composer run-script post-install-cmd || true

# Yarn install si node_modules absent
if [ ! -d node_modules ]; then
  echo "node_modules/ absent -> yarn install"
  yarn install
fi

echo "Build des assets..."
yarn build

echo "Ajustement des permissions pour www-data..."
chown -R www-data:www-data /var/www/Cinephoria_web || true

echo "Lancement du serveur apache"
exec apache2-foreground
