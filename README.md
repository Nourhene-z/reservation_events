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

Default web admin credentials:

- Username: `admin`
- Password: `admin1234`
- Admin login URL: `/admin/login`

5. Start server

```bash
php -S 127.0.0.1:8011 -t public public/index.php
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

Response includes WebAuthn options in `publicKey` for `navigator.credentials.get()`.

### Passkey verify

```bash
POST /api/passkey/verify
{
  "username": "user",
  "assertion": {
    "id": "<credentialId base64url>",
    "response": {
      "clientDataJSON": "<base64url>",
      "authenticatorData": "<base64url>",
      "signature": "<base64url>"
    }
  }
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
- Web form login is restricted to admin accounts only
- Passkey endpoints are now rate-limited with dedicated limiters
- Passkey challenge has a configurable short TTL
- Passkey registration and authentication now validate WebAuthn cryptographic proofs (origin, rpId hash, signature, sign counter)
