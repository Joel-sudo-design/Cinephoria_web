# Cinephoria_web

Application Symfony pour présenter les films du cinéma **"Le Cinéphoria"**.

- **Administrateur** : ajouter/supprimer des films et des séances, créer des comptes employés, modifier les mots de passe, afficher les statistiques (nombre de réservations par film sur 1 semaine).
- **Employé** : gérer des films et valider les avis.
- **Visiteur** : créer un compte, réserver en ligne, accéder à ses commandes une fois connecté.

Backend : Symfony (PHP 8.3)  
Frontend : HTML5, CSS, Bootstrap, JS (jQuery, Axios)  
Bases de données : MariaDB (SQL) + MongoDB (NoSQL)  
Déploiement local : Docker

Lien en ligne : https://cinephoria.joeldermont.fr

---

## Aperçu

![Aperçu de l'application](aperçu.png)

---

## Prérequis

Avant de commencer, installez :

- Docker Desktop
- Git

---

## Installation et Configuration

Dans Ubuntu (WSL), clonez le dépôt :

```bash
git clone https://github.com/Joel-sudo-design/Cinephoria_web.git
cd Cinephoria_web
```

### Configuration du fichier `.env`

Créez le fichier `.env` à la racine du projet avec les variables suivantes :

```env
# Environnement
APP_ENV=dev
APP_DEBUG=1
APP_SECRET=change-me-in-prod

# MariaDB (SQL)
DATABASE_URL=mysql://symfony:symfony@db:3306/cinephoria?serverVersion=11.4.0-MariaDB

# MongoDB (NoSQL)
MONGODB_URL=mongodb://mongodb:27017
MONGODB_DB=cinephoria

# Mailer (MailHog)
MAILER_DSN=smtp://mailhog:1025

# URL de l'application
APP_URL=http://localhost
```

> ⚠️ Le fichier `.env` ne doit jamais être versionné en production.
> Utilisez `.env.local` ou des variables d’environnement pour la prod.

---

## Utilisation

### Accès aux services
- Application web : http://localhost
- phpMyAdmin : http://localhost:8081
- Mongo Express : http://localhost:8082
- MailHog : http://localhost:8025

---

## Auteur

Joël DERMONT  
Développeur principal – https://github.com/Joel-sudo-design
