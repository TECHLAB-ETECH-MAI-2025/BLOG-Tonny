
# 📘 Blog Symfony

Un projet Symfony de blog avec authentification, back-office, et gestion d'utilisateurs.

## Prérequis

Avant de commencer, assurez-vous d'avoir installé les éléments suivants sur votre machine :

- [PHP](https://www.php.net/manual/fr/install.php) (version 8.0 ou supérieure)
- [Composer](https://getcomposer.org/download/)
- [Node.js](https://nodejs.org/) (pour npm)
- [Symfony CLI](https://symfony.com/download)

## Installation

1. **Cloner le dépôt**

   ```bash
   git clone <URL_DU_DÉPÔT>
   cd blog-symfony/back
   ```

2. **Installer les dépendances PHP avec Composer**

   ```bash
   composer install
   ```

3. **Configurer les variables d'environnement**

   Copiez le fichier `.env` et configurez vos variables d'environnement, notamment pour la base de données.

   ```bash
   cp .env .env.local
   ```

4. **Créer la base de données et exécuter les migrations**

   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate
   ```

5. **Installer les dépendances JavaScript avec npm**

   ```bash
   npm install
   ```

6. **Compiler les assets avec Webpack Encore**

   ```bash
   npm run dev
   ```

## Création d'un Utilisateur Administrateur

Pour créer un utilisateur administrateur, exécutez la commande suivante :

```bash
http://localhost:8000/create-my-blog-admin
```

Cette URL va créer un utilisateur administrateur avec les informations suivantes :

- Email : `admin@admin.test`
- Nom d'utilisateur : `admin`
- Mot de passe : `0000`

## Création de Données de Test

Pour créer des données de test, exécutez la commande suivante :

```bash
http://localhost:8000/create-test-data
```

Un utilisateur standard sera créé avec les informations suivantes :

- Email : `user@blog.test`
- Nom d'utilisateur : `testuser`
- Mot de passe : `1234`

## Démarrage des Serveurs

Pour lancer le serveur Mercure et le serveur PHP intégré, utilisez les commandes suivantes :

1. **Lancer Mercure**

   ```bash
   sudo ./mercure/mercure --jwt-key='eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJtZXJjdXJlIjp7InB1Ymxpc2giOlsiKiJdfX0.AeCSJEGE8f_gdPLQBxgQlznmq_Mu071r_wly5gCLKug' --addr='localhost:3000' --allow-anonymous --cors-allowed-origins='http://localhost:8000,http://localhost:5173'
   ```

2. **Lancer le serveur PHP intégré**

   ```bash
   php -S localhost:8000 -t public
   ```