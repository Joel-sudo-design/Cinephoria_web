#!/bin/bash
set -euo pipefail

echo "Attente du port 3306..."
/wait-for-it.sh db:3306 -t 60

echo "Création de la base si nécessaire..."
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:migrations:sync-metadata-storage --no-interaction || true

if find migrations -maxdepth 1 -type f -name '*.php' | grep -q .; then
  echo "Migrations détectées → on les applique"
  php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
else
  if [ ! -f var/.schema_bootstrapped ]; then
    echo "Aucune migration → bootstrap du schéma (dev & prod)"
    php bin/console doctrine:schema:update --force --no-interaction
    touch var/.schema_bootstrapped
  else
    echo "Schéma déjà bootstrappé → rien à faire"
  fi
fi

echo "Exécution des scripts post-install"
composer run-script post-install-cmd || true

echo "Installation front (yarn install) et build des assets (yarn build)"
yarn install
yarn build

echo "Ajustement des permissions"
chown -R www-data:www-data var public || true

echo "Lancement de PHP-FPM"
exec php-fpm -F
