#!/bin/bash
set -e

echo "Attente du port 3306..."
/wait-for-it.sh db:3306 -t 30

echo "Création de la base si besoin"
php bin/console doctrine:database:create --if-not-exists || true

if php bin/console dbal:run-sql "SHOW TABLES" 2>/dev/null | grep -qE '[[:alnum:]_]'; then
  echo "Base non vide -> on lance les migrations"
  php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration || true
else
  if ls -1 migrations/*.php >/dev/null 2>&1; then
    echo "Migrations détectées -> migrate"
    php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration || true
  else
    echo "DB vide et aucune migration -> schema:create"
    php bin/console doctrine:schema:create || true
  fi
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
