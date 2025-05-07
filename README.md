# HelpFlow

HelpFlow est une application backend développée avec **Symfony 6.4** et **API Platform 3.4** pour la gestion des tickets d'assistance technique. Elle est conçue pour les entreprises souhaitant centraliser la gestion des incidents, suivre les tickets, et notifier les techniciens en temps réel grâce à **Mercure** et **RabbitMQ**.

## Fonctionnalités principales

### 1. Authentification & gestion des comptes

-  **Inscription, login, mot de passe oublié**.
-  Gestion des rôles : **Admin**, **Manager**, **Technicien**, **Client**.

### 2. Gestion des organisations (entreprises clientes)

-  Une entreprise peut avoir plusieurs utilisateurs.
-  Chaque ticket appartient à une entreprise.

### 3. Gestion des utilisateurs

-  Attribution de rôles internes (Admin, Manager, Technicien, Client).

### 4. Gestion des tickets

-  Création, suivi, et changement de statut des tickets.
-  Gestion des priorités, types d’incidents, et descriptions.
-  Historique des changements.
-  Affectation automatique ou manuelle des tickets à un technicien.

### 6. Notifications

-  Notifications en temps réel via **Mercure**.
-  Exemples de notifications :
   -  Nouveau ticket créé.
   -  Ticket assigné à un technicien.
   -  Ticket clôturé.

---

## Prérequis

Avant de commencer, assurez-vous d'avoir les éléments suivants installés sur votre machine :

-  **PHP 8.1 ou supérieur**
-  **Composer**
-  **Docker et Docker Compose**

---

## Installation

### Étape 1 : Cloner le dépôt

```bash
git clone <url-du-repo>
cd HelpFlow
```

### Étape 2 : Installer les dépendances PHP

```bash
composer install
```

### Étape 3 : Configurer les variables d'environnement

-  Copiez le fichier `.env` :
   ```bash
   cp .env .env.local
   ```
-  Modifiez les valeurs dans `.env.local` selon votre configuration (base de données, JWT, Mercure, etc.).

### Étape 4 : Lancer les conteneurs Docker

```bash
docker-compose up -d
```

### Étape 5 : Configurer la base de données

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

### Étape 6 : Générer les clés JWT

```bash
mkdir -p config/jwt
openssl genrsa -out config/jwt/private.pem -aes256 4096
openssl rsa -pubout -in config/jwt/private.pem -out config/jwt/public.pem
```

### Étape 7 : Charger les données de test (fixtures)

```bash
php bin/console doctrine:fixtures:load
```

---

## Utilisation

### Lancer l'application

1. Assurez-vous que les conteneurs Docker sont en cours d'exécution :

   ```bash
   docker-compose up -d
   ```

2. Accédez à l'application via l'API Platform :
   ```
   http://localhost:8000/api
   ```

## Fonctionnalités techniques

### API Platform

-  Exposition des entités via des endpoints RESTful.
-  Documentation interactive via Swagger.

### RabbitMQ

-  Gestion des messages asynchrones pour l'affectation des tickets.

### Mercure

-  Notifications en temps réel pour les techniciens et les utilisateurs.

### Doctrine Fixtures

-  Chargement de données de test pour faciliter le développement.
