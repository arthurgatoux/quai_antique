# Quai Antique

Projet **fil rouge** de la formation **Studi** pour le **TP Développeur Web et Web Mobile** (titre professionnel DWWM).

Quai Antique est le site d'un restaurant. Les visiteurs consultent la carte et la galerie. Les clients créent un compte, réservent une table et gèrent leurs réservations. L'administrateur gère les photos de la galerie.

- Site : https://quaiantiquerestaurant17.alwaysdata.net
- API : https://quaiantique17-back.alwaysdata.net/api
- Documentation de l'API (Swagger) : https://quaiantique17-back.alwaysdata.net/api/doc

Auteur : Arthur Gatoux

---

## Fonctionnalités

| Profil | Fonctionnalités |
|---|---|
| Visiteur | Accueil, carte (formules, entrées, plats, desserts, boissons), galerie, inscription, connexion |
| Client | Réserver une table, consulter, modifier et annuler ses réservations, modifier ses informations (nom, prénom, allergies, nombre de convives habituel), changer son mot de passe |
| Administrateur | Tout ce que fait le client, plus l'ajout, la modification et la suppression des photos de la galerie, et la consultation de toutes les réservations |

Règles de réservation : la date ne peut pas être passée, et le nombre de convives par service (midi ou soir) ne peut pas dépasser la capacité du restaurant (`maxGuest`).

---

## Architecture

Un seul dépôt (monorepo) contient les deux applications.

```
quai_antique/
├── quai_antique_front/                      Front : SPA en JavaScript natif + Bootstrap/SASS
│   ├── index.html
│   ├── Router/                              Routeur maison (routes, droits d'accès par rôle)
│   ├── pages/                               Gabarits HTML de chaque page
│   ├── js/                                  Scripts de chaque page + script.js (cookies, rôles, apiFetch)
│   ├── scss/                                Styles (compilés en main.css)
│   └── .htaccess                            Réécriture vers index.html (navigation SPA)
└── quai_antique_back/
    └── studi-restaurant-symfony-lts-api/    Back : API REST Symfony 5.4
        ├── src/Entity/                      User, Restaurant, Picture, Booking, Menu, Category, Food
        ├── src/Controller/                  Contrôleurs de l'API
        ├── migrations/                      Migrations Doctrine
        ├── config/                          Sécurité, CORS, Nelmio
        └── seed_prod.sql                    Données de départ pour la production
```

### Stack technique

| Couche | Technologies |
|---|---|
| Front | HTML, JavaScript (modules ES), Bootstrap 5, SASS |
| Back | PHP 8.2, Symfony 5.4, Doctrine ORM, NelmioApiDocBundle, NelmioCorsBundle |
| Base de données | MySQL / MariaDB |
| Hébergement | Alwaysdata (deux comptes : un pour le back et la base, un pour le front) |
| Versioning | Git, GitHub |

### Sécurité

- Authentification par jeton d'API, envoyé dans l'en-tête `X-AUTH-TOKEN`.
- Rôles : `ROLE_USER` (client) et `ROLE_ADMIN`. Le front les traduit en `client` et `admin`.
- Les routes de lecture de la carte et de la galerie sont publiques. Les écritures de la carte et de la galerie sont réservées à l'admin.
- Un client n'accède qu'à ses propres réservations.
- Les textes affichés par le front sont insérés avec `textContent`, jamais en HTML brut.

---

## Routes de l'API

La documentation complète est sur `/api/doc`.

| Ressource | Routes | Accès |
|---|---|---|
| Compte | `POST /api/registration`, `POST /api/login` | Public |
| Profil | `GET /api/account/me`, `PUT /api/account/edit` | Connecté |
| Restaurant | `GET /api/restaurant/{id}` | Public |
| Galerie | `GET /api/picture`, `GET /api/picture/{id}` | Public |
| | `POST /api/picture`, `PUT` et `DELETE /api/picture/{id}` | Admin |
| Menus (formules) | `GET /api/menu`, `GET /api/menu/{id}` | Public |
| | `POST /api/menu`, `PUT` et `DELETE /api/menu/{id}` | Admin |
| Catégories | `GET /api/category`, `GET /api/category/{id}` | Public |
| | `POST /api/category`, `PUT` et `DELETE /api/category/{id}` | Admin |
| Plats | `GET /api/food`, `GET /api/food/{id}` | Public |
| | `POST /api/food`, `PUT` et `DELETE /api/food/{id}` | Admin |
| Réservations | `GET`, `POST /api/booking`, `GET`, `PUT`, `DELETE /api/booking/{id}` | Connecté (client : les siennes, admin : toutes) |

Codes de retour : `201` à la création, `204` à la modification et à la suppression, `404` si la ressource n'existe pas, `422` si les données sont invalides, `409` s'il n'y a plus de place pour un service.

---

## Installation en local

### Prérequis

PHP 8.2, Composer, Symfony CLI (facultatif), MySQL ou MariaDB, Node.js et npm.

### Back

```bash
cd quai_antique_back/studi-restaurant-symfony-lts-api
composer install
```

Créer un fichier `.env.local` (non versionné) :

```dotenv
DATABASE_URL="mysql://utilisateur:motdepasse@127.0.0.1:3306/quai_antique?serverVersion=mariadb-10.6.0&charset=utf8mb4"
```

Vérifier aussi que `CORS_ALLOW_ORIGIN` du fichier `.env` autorise l'adresse du front local. Puis :

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load     # données de test (utilisateurs, restaurant, images)
symfony serve                              # ou : php -S 127.0.0.1:8000 -t public
```

L'API répond sur `http://127.0.0.1:8000/api`.

### Front

```bash
cd quai_antique_front
npm ci
```

Le site doit être servi par un serveur statique qui renvoie `index.html` pour toutes les URL (par exemple `npx live-server --entry-file=index.html`). Sur `localhost` ou `127.0.0.1`, le front appelle automatiquement l'API locale. Ailleurs, il appelle l'API de production (voir `quai_antique_front/js/script.js`).

---

## Déploiement

Le cours propose un déploiement du back sur un PaaS (Platform.sh, devenu Upsun) et du front par FTP avec GitHub Actions. J'ai choisi Alwaysdata pour les deux, avec un déploiement en SSH et Git. Le FTPS d'Alwaysdata coupait les transferts depuis GitHub Actions, et le FTP simple y est refusé.

| Élément | Compte Alwaysdata | URL |
|---|---|---|
| Back et base de données | `quaiantique17-back` | https://quaiantique17-back.alwaysdata.net |
| Front | `quaiantiquerestaurant17` | https://quaiantiquerestaurant17.alwaysdata.net |

Le dépôt GitHub étant public, les deux comptes le clonent directement. Les mises à jour se font avec `git pull`.

### 1. Back et base de données (compte `quaiantique17-back`)

**Configuration dans le panel Alwaysdata**

1. **Environnement** : passer la version de PHP du compte à **8.2** (le projet exige `php 8.2.*`).
2. **Bases de données → MySQL** : créer la base et un utilisateur dédié. Le nom de la base est préfixé par le nom du compte, et l'hôte est `mysql-quaiantique17-back.alwaysdata.net`.
3. **Accès distant → SSH** : créer un utilisateur SSH.
4. **Web → Sites** : créer un site de type **PHP** avec l'adresse `quaiantique17-back.alwaysdata.net` et le répertoire racine `/quai_antique/quai_antique_back/studi-restaurant-symfony-lts-api/public/`. Le chemin est relatif au dossier personnel du compte.

**Récupérer le code (en SSH)**

Si le dépôt était privé, il fallait une clé de déploiement en lecture seule. Le dépôt est maintenant public, un simple clone suffit.

```bash
git clone https://github.com/arthurgatoux/quai_antique.git
cd quai_antique/quai_antique_back/studi-restaurant-symfony-lts-api
composer install --no-dev --optimize-autoloader
```

**Configuration de production**

Créer le fichier `.env.local` sur le serveur. Il n'est jamais versionné.

```dotenv
APP_ENV=prod
APP_SECRET=<chaîne aléatoire longue>
DATABASE_URL="mysql://<utilisateur>:<motdepasse>@mysql-quaiantique17-back.alwaysdata.net:3306/<base>?serverVersion=mariadb-10.6.0&charset=utf8mb4"
CORS_ALLOW_ORIGIN='^https://quaiantiquerestaurant17\.alwaysdata\.net$'
```

```bash
chmod 600 .env.local
```

Sans `APP_ENV=prod`, la console tourne en environnement de développement et `cache:clear` échoue (MakerBundle absent en production). Adapter `serverVersion` à la version de la base indiquée dans le panel.

**Base de données et données de départ**

```bash
php bin/console cache:clear
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:schema:validate
```

Charger ensuite le restaurant, la galerie, les catégories, les plats et les formules, **une seule fois** :

```bash
mysql -h mysql-quaiantique17-back.alwaysdata.net -u <utilisateur> -p <base> < seed_prod.sql
```

Le fichier peut aussi être importé depuis phpMyAdmin.

**Créer l'administrateur**

S'inscrire d'abord depuis le site, puis passer le compte en administrateur :

```sql
UPDATE user SET roles = '["ROLE_ADMIN"]' WHERE email = 'adresse@exemple.fr';
```

Se déconnecter puis se reconnecter pour que le rôle soit pris en compte.

**Vérification**

- `https://quaiantique17-back.alwaysdata.net/api/doc` répond `200`.
- `GET /api/menu` renvoie la liste des formules.
- Un appel avec l'en-tête `Origin: https://quaiantiquerestaurant17.alwaysdata.net` renvoie `access-control-allow-origin`.

### 2. Front (compte `quaiantiquerestaurant17`)

1. **Accès distant → SSH** : créer un utilisateur SSH, puis se connecter.
2. Récupérer le code et installer les dépendances (Bootstrap et Bootstrap Icons viennent de `node_modules`, qui n'est pas versionné) :
   ```bash
   git clone https://github.com/arthurgatoux/quai_antique.git
   cd quai_antique/quai_antique_front
   npm ci
   ```
3. **Web → Sites** : créer un site de type **Fichiers statiques** avec l'adresse `quaiantiquerestaurant17.alwaysdata.net` et le répertoire racine `/quai_antique/quai_antique_front/`.
4. Le fichier `.htaccess` du front renvoie toute URL inconnue vers `index.html`, ce qui permet d'actualiser une page comme `/galerie` sans erreur 404.
5. L'URL de l'API de production est déjà définie dans `js/script.js`. Elle est choisie selon le nom d'hôte.

**Vérification** : ouvrir le site, actualiser `/galerie`, créer un compte, se connecter, puis réserver une table.

### 3. Mettre à jour le site

Après un `git push` sur `main` :

```bash
# Front (compte quaiantiquerestaurant17)
cd ~/quai_antique && git pull
# npm ci seulement si package.json a changé

# Back (compte quaiantique17-back)
cd ~/quai_antique && git pull
cd quai_antique_back/studi-restaurant-symfony-lts-api
composer install --no-dev --optimize-autoloader   # seulement si composer.lock a changé
php bin/console cache:clear
php bin/console doctrine:migrations:migrate --no-interaction
```

## Évolutions possibles

- Page d'administration de la carte (plats et formules). Pour l'instant, ils se gèrent via `/api/doc`.
- Téléversement d'images dans la galerie (aujourd'hui, on saisit le nom d'un fichier du dossier `images/`).
- Suppression du compte par l'utilisateur.
- Tests automatisés (PHPUnit pour le back) et intégration continue.