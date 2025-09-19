#!/bin/bash
set -e

echo "Attente du port 3306..."
/wait-for-it.sh db:3306 -t 30

echo "Création de la base si nécessaire..."
php bin/console doctrine:database:create --if-not-exists

if ls migrations/*.php >/dev/null 2>&1; then
  echo "Migrations détectées → on les applique"
  php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
else
  echo "Aucune migration → création du schéma direct"
  php bin/console doctrine:schema:create --no-interaction
fi

echo "Execution des autres scripts"
composer run-script post-install-cmd || true

if [ ! -d node_modules ] || [ yarn.lock -nt node_modules ]; then
  echo "DEV: (re)installation des dépendances front"
  yarn install
fi

echo "Build des assets"
yarn build

echo "Ajustement des permissions"
chown -R www-data:www-data var public || true

echo "Lancement du serveur apache"
exec apache2-foreground
