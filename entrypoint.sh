#!/bin/bash
set -e

echo "🚀 Démarrage de l'application en production..."

# Vérifier les variables obligatoires
if [ -z "$APP_SECRET" ] || [ ${#APP_SECRET} -lt 32 ]; then
    echo "❌ ERREUR: APP_SECRET manquant ou trop court (min 32 chars)"
    exit 1
fi

# Extraire les infos DB depuis DATABASE_URL
DB_HOST=$(echo $DATABASE_URL | sed -n 's|.*@\([^:]*\):.*|\1|p')
DB_PORT=$(echo $DATABASE_URL | sed -n 's|.*:\([0-9]*\)/.*|\1|p')
DB_USER=$(echo $DATABASE_URL | sed -n 's|.*://\([^:]*\):.*|\1|p')
DB_PASS=$(echo $DATABASE_URL | sed -n 's|.*://[^:]*:\([^@]*\)@.*|\1|p')

# Attendre la base de données
echo "⏳ Attente de la base de données ($DB_HOST:$DB_PORT)..."
MAX_TRIES=60
COUNTER=0

until mysqladmin ping -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USER" -p"$DB_PASS" --silent 2>/dev/null; do
  COUNTER=$((COUNTER+1))
  if [ $COUNTER -gt $MAX_TRIES ]; then
    echo "❌ Impossible de se connecter à la base de données"
    exit 1
  fi
  sleep 2
done
echo "✅ Base de données prête !"

# Vérifier les dépendances
if [ ! -f "vendor/autoload.php" ]; then
  echo "❌ ERREUR: vendor/autoload.php manquant !"
  exit 1
fi

# Création DB + migrations
echo "🗄️  Création de la base de données..."
php bin/console doctrine:database:create --if-not-exists --no-interaction

echo "📄 Application des migrations..."
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

# Cache
echo "🧹 Nettoyage du cache..."
php bin/console cache:clear --no-warmup

echo "🔥 Préchauffage du cache..."
php bin/console cache:warmup

# Permissions Caddy
mkdir -p var/caddy
chmod 755 var/caddy

echo ""
echo "╔════════════════════════════════════════╗"
echo "║ ✅ Application prête en PRODUCTION !   ║"
echo "╠════════════════════════════════════════╣"
echo "║ 🌐 Port : 443 (HTTPS)                  ║"
echo "║ 📊 Environnement : ${APP_ENV}          ║"
echo "║ 🐛 Debug : ${APP_DEBUG:-0}             ║"
echo "╚════════════════════════════════════════╝"
echo ""

exec "$@"