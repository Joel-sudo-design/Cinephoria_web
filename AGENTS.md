# Cinephoria — contexte durable

## Stack et environnement local

- Application Symfony exécutée avec PHP 8.4 et FrankenPHP.
- Frontend compilé avec Webpack Encore, Node.js 22 et Yarn.
- Données SQL sous MariaDB et données NoSQL sous MongoDB.
- Environnement local Docker Compose défini dans `docker-compose.yml`.
- Nom Compose existant : `cinephoria`.
- Conteneurs existants : `cinephoria`, `symfony_db_cinephoria`, `mongo_cinephoria`, `phpmyadmin_cinephoria` et `mongo_express_cinephoria`.
- Le Compose actuel publie l'application sur les ports hôte `80` et `443`, phpMyAdmin sur `127.0.0.1:8081` et Mongo Express sur `127.0.0.1:8082`. Aucun service MailHog ou Mailpit n'y est actuellement défini.

## Vérifications

- Installer les dépendances frontend : `yarn install --frozen-lockfile`.
- Compiler les assets : `yarn build`.
- Valider Compose : `docker compose config`.
- Construire l'image complète : `docker build --tag application_web_cinephoria:local .`.
- Lancer l'environnement : `docker compose up -d --build`.
- Exécuter les tests PHP dans un environnement contenant les dépendances de développement : `php vendor/bin/phpunit --bootstrap vendor/autoload.php tests`. Le dépôt ne contient pas de `phpunit.xml` ni de lanceur `bin/phpunit` ; l'environnement `test` ajoute le suffixe `_test` au nom de la base, dont le schéma doit donc être préparé avant l'exécution.
- Après une modification d'interface, vérifier obligatoirement l'application locale sur ordinateur, tablette et mobile avec le navigateur de contrôle, puis contrôler la console et les requêtes réseau.

## CI et déploiement

- Le workflow `.github/workflows/ci.yml` valide les assets et la construction de l'image sur les pull requests, les pushes vers `main` et les lancements manuels.
- Le précédent serveur de production n'est plus une cible valide : le domaine `cinephoria.joeldermont.fr` ne résout plus et l'ancien VPS n'appartient plus aux services OVH actifs vérifiés le 12 août 2026.
- Aucun déploiement distant ne doit être réactivé avant d'avoir désigné un serveur actif, créé ou vérifié sa session MobaXterm, confirmé sa clé SSH, son port et son dossier de déploiement, puis renouvelé les secrets GitHub correspondants.
- Un futur déploiement doit préserver les volumes MariaDB, MongoDB, les images téléversées et les clés JWT.
- Les valeurs secrètes restent dans l'environnement sécurisé ou dans les secrets GitHub et ne doivent jamais être ajoutées au dépôt.

## Maintenance des dépendances

- Dependabot couvre Composer, npm, Docker, Docker Compose et GitHub Actions.
- Toute proposition doit réussir la CI et les vérifications locales pertinentes avant fusion.
- Les mises à niveau majeures de MariaDB ou MongoDB exigent une sauvegarde vérifiée, un test de migration local et une procédure de retour arrière avant toute utilisation sur un serveur contenant des données.
- Les mises à niveau majeures de Symfony ou d'un composant applicatif doivent être regroupées et testées fonctionnellement avant fusion.
- Le bundle `endroid/qr-code-bundle` version 7 utilise `endroid_qr_code.builders.default`; ses routes sont enregistrées automatiquement et aucun fichier dédié sous `config/routes/` n'est nécessaire.
- L'image de production doit conserver `opcache.save_comments=1`, requis par la compilation de configuration Symfony, et `.dockerignore` doit exclure `vendor/` afin de préserver les dépendances construites dans l'image.
- Les notifications de build Webpack restent désactivées : `webpack-notifier` dépend d'une branche `uuid` vulnérable et n'est pas nécessaire à la compilation ni au fonctionnement du site.
- Les scripts shell, notamment `entrypoint.sh`, doivent rester en fins de ligne LF ; `.gitattributes` impose ce format afin que leur shebang soit exécutable dans Linux.

## Données et contenu

- La base SQL, MongoDB, `public/image_film`, les clés JWT et les fichiers `.env` ne doivent jamais être remplacés par un déploiement normal.
- `transaction.sql` sert uniquement à initialiser des données de démonstration et ne doit jamais être importé automatiquement sur une cible contenant des données.
