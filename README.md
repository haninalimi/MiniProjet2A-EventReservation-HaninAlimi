# EventApp — Application de Gestion de Réservations d'Événements

> **Mini Projet FIA4-GL · ISSAT Sousse · Année universitaire 2025–2026**

[![Symfony](https://img.shields.io/badge/Symfony-7.4.7-black?logo=symfony&logoColor=white)](https://symfony.com)
[![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?logo=php&logoColor=white)](https://php.net)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-15-336791?logo=postgresql&logoColor=white)](https://postgresql.org)
[![JWT](https://img.shields.io/badge/Auth-JWT%20%2B%20Passkeys-D4A843)](https://jwt.io)
[![Docker](https://img.shields.io/badge/Docker-Ready-2496ED?logo=docker&logoColor=white)](https://docker.com)
[![Tests](https://img.shields.io/badge/Tests-19%2F19%20passing-4CAF82)](https://phpunit.de)

---

## Présentation

**EventApp** est une application web complète de gestion et de réservation d'événements. Elle permet :

- Aux **utilisateurs** de consulter, rechercher et réserver des événements en ligne avec une authentification sécurisée JWT + Passkeys (WebAuthn FIDO2)
- Aux **administrateurs** de gérer le catalogue d'événements via un dashboard professionnel (CRUD complet)

Le projet est développé avec **Symfony 7.4** et suit une architecture MVC propre, avec une séparation claire entre le frontend utilisateur  et l'interface d'administration .

---

## Technologies utilisées

| Technologie | Version | Rôle |
|---|---|---|
| PHP | 8.3 | Langage backend |
| Symfony | 7.4.7 | Framework principal |
| PostgreSQL | 15 | Base de données |
| lexik/jwt-authentication-bundle | 3.x | Authentification JWT |
| gesdinet/jwt-refresh-token-bundle | 1.x | Refresh tokens |
| web-auth/webauthn-lib | 4.x | Passkeys / WebAuthn FIDO2 |
| nelmio/cors-bundle | 2.x | Gestion CORS API |
| Docker | latest | Containerisation |
| PHPUnit | 12.x | Tests unitaires et fonctionnels |

---

## Architecture du projet

```
event-reservation/
├── src/
│   ├── Controller/
│   │   ├── AuthApiController.php      # API JWT + Passkeys
│   │   ├── EventController.php        # Pages événements utilisateur
│   │   ├── HomeController.php         # Page d'accueil
│   │   ├── AdminController.php        # Dashboard admin (CRUD)
│   │   └── SecurityController.php     # Login / Register / Logout
│   ├── Entity/
│   │   ├── Event.php                  # Entité événement
│   │   ├── Reservation.php            # Entité réservation
│   │   ├── User.php                   # Entité utilisateur
│   │   ├── Admin.php                  # Entité administrateur
│   │   └── WebauthnCredential.php     # Clés Passkey
│   ├── Repository/                    # Requêtes DQL personnalisées
│   ├── Form/
│   │   ├── EventType.php              # Formulaire événement
│   │   └── ReservationType.php        # Formulaire réservation
│   ├── Service/
│   │   └── PasskeyAuthService.php     # Logique WebAuthn FIDO2
│   └── Command/
│       └── CreateAdminCommand.php     # Commande créer admin
├── templates/
│   ├── base.html.twig                 # Layout utilisateur 
│   ├── home/                          # Page d'accueil
│   ├── event/                         # Pages événements
│   ├── auth/                          # Login / Register
│   └── admin/                         # Dashboard admin
├── public/
│   ├── css/admin.css                  # Design system admin séparé
│   └── js/admin.js                    # Modules JS admin séparés
├── tests/
│   └── Controller/
│       ├── AuthApiControllerTest.php  # Tests API JWT
│       └── EventControllerTest.php    # Tests routes frontend
├── config/
│   ├── packages/security.yaml         # Firewalls JWT + Admin + User
│   └── jwt/                           # Clés RSA privée/publique
├── migrations/                        # Migrations Doctrine
├── docker-compose.yml                 # Configuration Docker
└── Dockerfile                         # Image Docker
```

---

## Fonctionnalités

### Côté utilisateur
- ✅ Authentification JWT (login / register)
- ✅ Connexion biométrique via **Passkeys (WebAuthn FIDO2)**
- ✅ Affichage de la liste des événements avec recherche et filtres
- ✅ Consultation du détail d'un événement
- ✅ Formulaire de réservation (nom, email, téléphone)
- ✅ Vérification des places disponibles en temps réel
- ✅ Prévention des doublons (même email / même événement)
- ✅ Message de confirmation après réservation

### Côté administrateur
- ✅ Authentification sécurisée (session classique)
- ✅ Dashboard avec statistiques en temps réel
- ✅ CRUD complet sur les événements (créer, lire, modifier, supprimer)
- ✅ Formulaires en modal popup (sans changer de page)
- ✅ Validation côté client avant envoi au serveur
- ✅ Confirmation de suppression avec popup personnalisé
- ✅ Consultation des réservations par événement
- ✅ Upload d'images pour les événements
- ✅ Déconnexion sécurisée

### Sécurité
- ✅ JWT + Refresh Tokens (TTL 1h / 30 jours)
- ✅ Passkeys FIDO2 (WebAuthn)
- ✅ Protection CSRF sur les formulaires
- ✅ Firewalls séparés (API stateless / Admin session / User session)
- ✅ Hachage bcrypt des mots de passe
- ✅ Validation serveur + client sur tous les formulaires

---

## Installation

### Prérequis

- PHP 8.3+
- Composer
- PostgreSQL 15
- Symfony CLI
- OpenSSL

### 1. Cloner le dépôt

```bash
git clone https://github.com/haninalimi/MiniProjet2A-EventReservation-HaninAlimi.git
cd MiniProjet2A-EventReservation-HaninAlimi
```

### 2. Installer les dépendances

```bash
composer install
```

### 3. Configurer l'environnement

```bash
cp .env .env.local
```

Éditer `.env.local` :

```env
DATABASE_URL="postgresql://postgres:postgres@127.0.0.1:5432/event_reservation?serverVersion=15&charset=utf8"
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=event2026
JWT_TOKEN_TTL=3600
WEBAUTHN_RP_NAME="Event Reservation App"
APP_DOMAIN=localhost
```

### 4. Créer la base de données

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

### 5. Générer les clés JWT

```bash
php bin/console lexik:jwt:generate-keypair
```

### 6. Créer un administrateur

```bash
php bin/console app:create-admin admin admin123
```

### 7. Lancer le serveur

```bash
symfony server:start
```

Ouvrir `http://127.0.0.1:8000`

---

## Avec Docker

```bash
docker-compose up -d
```

L'application sera disponible sur `http://localhost:8080`

---

## URLs principales

### Interface utilisateur

| URL | Description |
|---|---|
| `/` | Page d'accueil |
| `/events` | Catalogue des événements |
| `/events/{id}` | Détail d'un événement |
| `/events/{id}/reserve` | Formulaire de réservation |
| `/login` | Connexion (JWT / Passkey) |
| `/register` | Inscription |

### Interface admin

| URL | Description |
|---|---|
| `/admin/login` | Connexion administrateur |
| `/admin` | Dashboard |
| `/admin/events` | Gestion des événements |
| `/admin/events/{id}/reservations` | Réservations par événement |

### API REST

| Méthode | Endpoint | Description |
|---|---|---|
| `POST` | `/api/auth/register` | Inscription |
| `POST` | `/api/auth/login` | Connexion JWT |
| `GET` | `/api/auth/me` | Profil utilisateur |
| `POST` | `/api/auth/passkey/register/options` | Options Passkey |
| `POST` | `/api/auth/passkey/register/verify` | Enregistrer Passkey |
| `POST` | `/api/auth/passkey/login/options` | Options login Passkey |
| `POST` | `/api/auth/passkey/login/verify` | Vérifier login Passkey |
| `POST` | `/api/token/refresh` | Refresh JWT token |

---

## Tests

```bash
# Créer la base de test
php bin/console doctrine:database:create --env=test
php bin/console doctrine:migrations:migrate --env=test --no-interaction

# Lancer les tests
php bin/phpunit --testdox
```


```
Auth Api Controller
 ✔ Register success
 ✔ Register duplicate email
 ✔ Register missing fields
 ✔ Register short password
 ✔ Login via register gives token
 ✔ Login wrong password
 ✔ Me with valid token
 ✔ Me unauthenticated
 ✔ Me invalid token

Event Controller
 ✔ Event index is public
 ✔ Event show not found
 ✔ Home page is accessible
 ✔ Login page is accessible
 ✔ Register page is accessible
 ✔ Admin requires auth
 ✔ Admin login page is accessible
 ✔ Reserve event not found
 ✔ Confirm not found
 ✔ Api me without token
```

---

## Branches Git

| Branche | Rôle |
|---|---|
| `main` | Code stable et livrable |
| `dev` | Intégration et tests |
| `feature/backend-crud` | CRUD admin + sécurité + design |
| `feature/entities` | Entités Doctrine |
| `feature/jwt-passkeys` | Authentification JWT + WebAuthn |

---

## Auteur

**Hanin Alimi**
- Classe : FIA3-GL
- Institut : ISSAT Sousse
- Année universitaire : 2025–2026
- Dépôt : [github.com/haninalimi/MiniProjet2A-EventReservation-HaninAlimi](https://github.com/haninalimi/MiniProjet2A-EventReservation-HaninAlimi)

---

*Mini Projet — Application Web de Gestion de Réservations d'Événements · Technologies : Symfony + JWT + Passkeys · FIA4-GL · ISSAT Sousse · 2026*