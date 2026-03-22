#!/bin/bash
set -e

echo "🚀 Démarrage de l'application..."

# Extraire les infos de connexion depuis DATABASE_URL
DB_HOST=$(echo $DATABASE_URL | sed -n 's|.*@\([^:]*\):.*|\1|p')
DB_PORT=$(echo $DATABASE_URL | sed -n 's|.*:\([0-9]*\)/.*|\1|p')
DB_USER=$(echo $DATABASE_URL | sed -n 's|.*://\([^:]*\):.*|\1|p')
DB_PASS=$(echo $DATABASE_URL | sed -n 's|.*://[^:]*:\([^@]*\)@.*|\1|p')

# Attendre que la base de données soit prête
echo "⏳ Attente de la base de données ($DB_HOST:$DB_PORT)..."
MAX_TRIES=60
COUNTER=0

until mysqladmin ping -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USER" -p"$DB_PASS" --silent 2>/dev/null; do
  COUNTER=$((COUNTER+1))
  if [ $COUNTER -gt $MAX_TRIES ]; then
    echo "❌ Impossible de se connecter à la base de données après ${MAX_TRIES} tentatives"
    exit 1
  fi
  sleep 3
done
echo "✅ Base de données prête !"

# Installation des dépendances Composer (uniquement si nécessaire)
if [ ! -f "vendor/autoload.php" ] || [ "composer.lock" -nt "vendor/autoload.php" ]; then
  echo "📦 Installation des dépendances Composer..."
  composer install --no-interaction --optimize-autoloader
else
  echo "✅ Dépendances Composer déjà à jour"
fi

# Création de la base de données si nécessaire
echo "🗄️  Création de la base de données si nécessaire..."
php bin/console doctrine:database:create --if-not-exists --no-interaction

# Vérifier si des migrations existent
MIGRATIONS_DIR="migrations"
if [ ! -d "$MIGRATIONS_DIR" ] || [ -z "$(ls -A $MIGRATIONS_DIR 2>/dev/null)" ]; then
  echo "⚠️  Aucune migration trouvée"
  mkdir -p "$MIGRATIONS_DIR"

  # Vérifier s'il existe des entités
  if [ -d "src/Entity" ] && [ -n "$(ls -A src/Entity 2>/dev/null)" ]; then
    echo "🔧 Génération des migrations depuis les entités..."
    php bin/console make:migration --no-interaction || true
  else
    echo "ℹ️  Aucune entité trouvée, création du schéma vide..."
    php bin/console doctrine:schema:create --no-interaction || true
  fi
else
  echo "✅ Migrations trouvées"
fi

# Application des migrations
echo "🔄 Application des migrations..."
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

# Vérifier si des fixtures sont disponibles
if php bin/console list doctrine:fixtures:load > /dev/null 2>&1; then
  if [ "$APP_ENV" != "prod" ]; then
    echo "📊 Chargement des fixtures (environnement: $APP_ENV)..."
    php bin/console doctrine:fixtures:load --no-interaction || echo "⚠️  Erreur lors du chargement des fixtures (ignorée)"
  fi
fi

# Installation des dépendances Node.js (uniquement si nécessaire)
if [ -f "package.json" ]; then
  if [ ! -d "node_modules" ] || [ -z "$(ls -A node_modules 2>/dev/null)" ] || [ "package.json" -nt "node_modules" ]; then
    echo "📦 Installation des dépendances Node.js..."
    yarn install
  else
    echo "✅ Dépendances Node.js déjà à jour"
  fi

  # Build des assets
  echo "🎨 Build des assets..."
  if [ "$APP_ENV" = "prod" ]; then
    yarn build || echo "⚠️  Build des assets échoué (ignoré)"
  else
    yarn dev || echo "⚠️  Build des assets échoué (ignoré)"
  fi
else
  echo "ℹ️  Aucun package.json trouvé, assets ignorés"
fi

# Vider le cache Symfony
echo "🧹 Nettoyage du cache..."
php bin/console cache:clear --no-warmup

# Cache warmup uniquement en prod
if [ "$APP_ENV" = "prod" ]; then
  echo "🔥 Préchauffage du cache..."
  php bin/console cache:warmup
fi

echo ""
echo "════════════════════════════════════════"
echo "✅ Application prête !"
echo "🌐 Accès : http://localhost:8000"
echo "📝 Environnement : ${APP_ENV:-dev}"
echo "════════════════════════════════════════"
echo ""

# Lancer la commande passée en argument (FrankenPHP)
exec "$@"