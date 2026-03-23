# EventApp — Application de Gestion de Réservations d'Événements

> Mini Projet FIA4-GL · ISSAT Sousse · 2026

[![Symfony](https://img.shields.io/badge/Symfony-7.4-black?logo=symfony)](https://symfony.com)
[![PHP](https://img.shields.io/badge/PHP-8.3-blue?logo=php)](https://php.net)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-15-blue?logo=postgresql)](https://postgresql.org)
[![JWT](https://img.shields.io/badge/Auth-JWT%20%2B%20Passkeys-gold)](https://jwt.io)
[![Docker](https://img.shields.io/badge/Docker-Ready-blue?logo=docker)](https://docker.com)

---

## Présentation

**EventApp** est une application web complète de réservation d'événements développée avec Symfony 7. Elle permet :

- Aux **utilisateurs** de consulter, rechercher et réserver des événements en ligne
- Aux **administrateurs** de gérer le catalogue d'événements via une interface sécurisée
- Une **authentification renforcée** avec JWT + Passkeys (WebAuthn FIDO2)

---

## Stack Technique

| Technologie | Version | Rôle |
|---|---|---|
| PHP | 8.3 | Backend |
| Symfony | 7.4.7 | Framework principal |
| PostgreSQL | 15 | Base de données |
| lexik/jwt-authentication-bundle | 3.x | Authentification JWT |
| gesdinet/jwt-refresh-token-bundle | 1.x | Refresh tokens |
| web-auth/webauthn-lib | 4.x | Passkeys / WebAuthn FIDO2 |
| nelmio/cors-bundle | 2.x | CORS API |
| Docker | latest | Containerisation |


---

## Installation

### Prérequis

- PHP 8.3+
- Composer
- PostgreSQL 15
- Node.js (optionnel)
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

Édite `.env.local` :

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

### 6. Créer un compte administrateur

```bash
php bin/console app:create-admin admin admin123
```

### 7. Lancer le serveur

```bash
symfony server:start
```

Ouvre `http://127.0.0.1:8000`

---

## Avec Docker

```bash
docker-compose up -d
```

L'application sera disponible sur `http://localhost:8080`

---

## Utilisation

### Interface utilisateur

| URL | Description |
|---|---|
| `/` | Page d'accueil |
| `/events` | Liste des événements |
| `/events/{id}` | Détail d'un événement |
| `/events/{id}/reserve` | Formulaire de réservation |
| `/login` | Connexion JWT / Passkey |
| `/register` | Inscription |

### Interface admin

| URL | Description |
|---|---|
| `/admin/login` | Connexion admin |
| `/admin` | Dashboard |
| `/admin/events` | Gestion des événements |
| `/admin/events/{id}/reservations` | Réservations par événement |

### API REST

| Méthode | URL | Description |
|---|---|---|
| POST | `/api/auth/register` | Inscription |
| POST | `/api/auth/login` | Connexion JWT |
| GET | `/api/auth/me` | Profil utilisateur |
| POST | `/api/auth/passkey/register/options` | Options Passkey |
| POST | `/api/auth/passkey/register/verify` | Enregistrer Passkey |
| POST | `/api/auth/passkey/login/options` | Options login Passkey |
| POST | `/api/auth/passkey/login/verify` | Vérifier login Passkey |
| POST | `/api/token/refresh` | Refresh JWT token |

---

## Tests

```bash
# Créer la base de test
php bin/console doctrine:database:create --env=test
php bin/console doctrine:migrations:migrate --env=test

# Lancer les tests
php bin/phpunit

# Avec coverage
php bin/phpunit --coverage-text
```

---

## Branches Git

| Branche | Rôle |
|---|---|
| `main` | Code stable et livrable |
| `dev` | Intégration et tests |
| `feature/backend-crud` | CRUD admin + sécurité |
| `feature/tests-readme` | Tests + documentation |

---

## Auteur

**Hanin Alimi** — FIA4-GL · ISSAT Sousse  
Année universitaire 2025–2026

---

## Licence

Projet académique — ISSAT Sousse 2026