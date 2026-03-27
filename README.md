# Symfony App - Event Reservation

This folder contains the Symfony migration of the original event reservation project.

## Implemented professor requirements (chat specification)

- Symfony migration: done
- JWT authentication API: done
- Passkeys backend flow: done (challenge issuance, registration, verification endpoints)
- Dockerization: done (PHP + Nginx + MariaDB compose stack)
- GitHub workflow: done (CI with install, lint, migrations)

## Run locally (without Docker)

1. Install dependencies

```bash
composer install
```

2. Configure database in `.env`

Default is:

```env
DATABASE_URL="mysql://root:@127.0.0.1:3306/mini_event_db?charset=utf8mb4"
```

3. Apply migrations

```bash
php bin/console doctrine:migrations:migrate --no-interaction
```

4. Seed demo accounts

```bash
php bin/console app:create-demo-users
```

5. Start server

```bash
php -S 127.0.0.1:8011 -t public
```

## API quick tests

### JWT login

```bash
POST /api/login
{
  "username": "user",
  "password": "user1234"
}
```

### Protected endpoint

```bash
GET /api/me
Authorization: Bearer <token>
```

### Passkey options

```bash
POST /api/passkey/options
{
  "username": "user"
}
```

### Passkey verify

```bash
POST /api/passkey/verify
{
  "username": "user",
  "challenge": "...",
  "credentialId": "..."
}
```

## Docker

```bash
docker compose up -d --build
```

- App URL: http://127.0.0.1:8080
- DB host from app container: `database`
- DB port exposed locally: `3306`

## Security notes

- JWT is implemented with HS256 (`firebase/php-jwt`)
- Login throttling is enabled for both web and API logins
- Passkey challenge has a configurable short TTL
- Passkey verify now requires an existing credential bound to the user
