# Cinephoria_web

Application Symfony pour la gestion et la présentation des films du cinéma **"Le Cinéphoria"**.

- **Administrateur** : ajouter/supprimer des films et des séances, créer des comptes employés, modifier les mots de passe, consulter les statistiques (réservations par film).
- **Employé** : gérer les films et valider les avis.
- **Visiteur** : créer un compte, réserver en ligne, accéder à ses commandes et à l’historique.

Backend : Symfony (PHP 8.4 – FrankenPHP)  
Frontend : HTML5, CSS, JavaScript (AJAX, Axios)  
Bases de données : MariaDB (SQL) + MongoDB (NoSQL)  
Déploiement local : Docker

Lien en ligne : https://cinephoria.joeldermont.fr

---

## Aperçu

![Aperçu de l'application](aperçu.png)

---

## Prérequis

Avant de commencer, installez :

- [Docker Desktop](https://www.docker.com/products/docker-desktop/)
- [Git](https://git-scm.com/)

### Activer et installer WSL2 (Windows uniquement)

Si vous êtes sur **Windows**, l’utilisation de **WSL2** est fortement recommandée pour une compatibilité optimale avec Docker et Symfony.

1. Activer WSL et la virtualisation :
   ```powershell
   dism.exe /online /enable-feature /featurename:Microsoft-Windows-Subsystem-Linux /all /norestart
   dism.exe /online /enable-feature /featurename:VirtualMachinePlatform /all /norestart
   ```

2. Installer la mise à jour du noyau Linux :  
   https://aka.ms/wsl2kernel

3. Définir WSL2 comme version par défaut :
   ```powershell
   wsl --set-default-version 2
   ```

4. Installer une distribution Linux (ex : Ubuntu 22.04 LTS) :
   ```powershell
   wsl --install -d Ubuntu-22.04
   ```

5. Vérifier l’installation :
   ```powershell
   wsl --list --verbose
   ```

6. Activer l’intégration Docker avec WSL :
   - Docker Desktop → Settings → Resources → WSL Integration
   - Activer la distribution Linux utilisée

---

## Installation et Configuration

Dans Ubuntu (WSL), clonez le dépôt :

```bash
git clone https://github.com/Joel-sudo-design/Cinephoria_web.git
cd Cinephoria_web
```

### Configuration du fichier `.env`

Créez un fichier `.env` à la racine du projet :

```env
# Environnement
APP_ENV=dev
APP_DEBUG=1
APP_SECRET=change-me-in-prod

# MariaDB (SQL)
DATABASE_URL=mysql://root:root@db:3306/cinephoria?serverVersion=11.4.0-MariaDB

# MongoDB (NoSQL)
MONGODB_URL=mongodb://mongodb:27017
MONGODB_DB=cinephoria

# Mailer (MailHog)
MAILER_DSN=smtp://mailhog:1025

# FrankenPHP
SERVER_NAME=:8000
FRANKENPHP_CONFIG="num_threads 4"

# URL application
APP_URL=http://localhost:8000
```

> ⚠️ Ne jamais versionner le fichier `.env` en production.  
> Utiliser `.env.local` ou des variables d’environnement.

---

## Lancement de l’environnement

Construire et démarrer les conteneurs :

```bash
docker compose up -d --build
```

Le premier démarrage :
- Installe les dépendances PHP (`composer install`)
- Crée et migre les bases de données
- Installe et build les assets front si nécessaire

---

## Utilisation

### Accès aux services

- **Application web** : http://localhost:8000
- **phpMyAdmin (MariaDB)** : http://localhost:8081
- **Mongo Express (MongoDB)** : http://localhost:8082
- **MailHog** : http://localhost:8025

### Tests emails

Toutes les adresses emails fictives sont acceptées (ex : `test@cinephoria.fr`).  
Les emails sont interceptés par **MailHog** pour faciliter les tests sans SMTP externe.

---

## Commandes utiles

Logs applicatifs :
```bash
docker compose logs -f app
```

Accès au conteneur Symfony :
```bash
docker compose exec app bash
```

Arrêt de l’environnement :
```bash
docker compose down
```


---

## Gestion des rôles utilisateurs (phpMyAdmin)

La gestion des rôles n’est pas exposée via l’interface graphique.  
Elle se fait directement en base de données via **phpMyAdmin**.

### Étapes

1. Accéder à **phpMyAdmin** :  
   http://localhost:8081

2. Sélectionner la base de données **cinephoria**.

3. Ouvrir la table `user`.

4. Éditer l’utilisateur concerné.

5. Modifier le champ `roles` avec l’une des valeurs suivantes :

["ROLE_ADMIN"]

["ROLE_EMPLOYE"]

["ROLE_USER"]

6. Enregistrer les modifications.

---

## Insertion des données – `transaction.sql`

Un fichier **`transaction.sql`** est fourni à la racine du projet pour initialiser rapidement l’application avec des données de test :

- Films
- Cinéma
- Genre
- Salles
- Séances
- Réservations

### Import du fichier SQL

#### Méthode 1 – Via phpMyAdmin (recommandée)

1. Ouvrir **phpMyAdmin** : http://localhost:8081
2. Sélectionner la base **cinephoria**
3. Onglet **Importer**
4. Sélectionner le fichier `transaction.sql`
5. Cliquer sur **Exécuter**

#### Méthode 2 – En ligne de commande

```bash
docker compose exec db mysql -u root -proot cinephoria < transaction.sql
```

### Remarques importantes

- L’import doit être effectué **après** les migrations Doctrine.
- Si des données existent déjà, il est recommandé de vider la base avant import.
- Le fichier peut être réutilisé pour :
   - Réinitialiser l’environnement
   - Démonstrations
   - Tests fonctionnels

---


## Auteur

Joël DERMONT  
Développeur principal – https://github.com/Joel-sudo-design
