#!/bin/bash
set -euo pipefail

echo "Attente du port 3306..."
/wait-for-it.sh db:3306 -t 60

echo "Création de la base si nécessaire..."
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:migrations:sync-metadata-storage --no-interaction || true

echo "Validation du mapping Doctrine (diagnostic)"
php bin/console doctrine:schema:validate --skip-sync || true

LOCKFILE=/tmp/doctrine-migrate.lock
exec 9>"$LOCKFILE" && flock -n 9 || { echo "Une autre instance migre déjà. On attend..."; flock 9; }

has_migrations() {
  find migrations -maxdepth 1 -type f -name '*.php' | grep -q .
}

db_is_empty() {
  php bin/console doctrine:query:sql \
    "SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = DATABASE()
       AND table_name <> 'doctrine_migration_versions'" \
    | awk 'NR==2 {exit ($1>0)?1:0}'
}

if has_migrations; then
  echo "Migrations détectées → on les applique"
  php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
else
  if [ "${APP_ENV:-dev}" = "dev" ]; then
    echo "Aucune migration (DEV) → schema:update"
    php bin/console doctrine:schema:update --force --no-interaction
  else
    if db_is_empty; then
      echo "Aucune migration & DB vide (PROD) → bootstrap du schéma"
      php bin/console doctrine:schema:update --force --no-interaction
    else
      echo "Aucune migration & DB déjà peuplée (PROD) → on ne touche pas au schéma"
    fi
  fi
fi

echo "Exécution des scripts post-install"
composer run-script post-install-cmd || true

echo "Ajustement des permissions"
chown -R www-data:www-data var public || true

echo "Lancement de PHP-FPM"
exec php-fpm -F
