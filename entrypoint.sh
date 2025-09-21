#!/bin/bash
set -euo pipefail

echo "Attente du port 3306..."
/wait-for-it.sh db:3306 -t 60

echo "Création de la base si nécessaire..."
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:migrations:sync-metadata-storage --no-interaction || true

if find migrations -maxdepth 1 -type f -name '*.php' | grep -q .; then
  php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
else
  if [ "${APP_ENV:-dev}" != "prod" ]; then
    echo "Aucune migration trouvée → création/synchro du schéma en DEV"
    php bin/console doctrine:schema:update --force --no-interaction
  else
    echo "Aucune migration trouvée en PROD → on ne touche pas au schéma"
  fi
fi

echo "Exécution des scripts post-install"
composer run-script post-install-cmd || true

if [ "${APP_ENV:-dev}" != "prod" ]; then
  if [ ! -d node_modules ] || [ yarn.lock -nt node_modules ]; then
    echo "DEV: (re)installation des dépendances front"
    yarn install
  fi
  echo "Build des assets"
  yarn build
else
  echo "PROD: assets déjà buildés dans l'image"
fi

echo "Ajustement des permissions"
chown -R www-data:www-data var public || true

echo "Lancement de PHP-FPM"
exec php-fpm -F
