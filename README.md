# PHP Product Catalog CRUD

A compact server-rendered PHP and MySQL product catalog demonstrating create, read, update, and delete operations with PDO.

## Requirements

- PHP 8.1 or newer with `pdo_mysql` and `mbstring`
- MySQL 8.0.16 or newer, or a compatible MariaDB version that supports the schema

## Setup

1. Run `banco.sql` using an administrative database account.
2. Create a least-privilege database user with access only to `product_catalog`.
3. Set `DB_DSN`, `DB_USER`, and `DB_PASSWORD` from `.env.example` in your process environment.
4. Start the development server:

```sh
php -S localhost:8000
```

Open `http://localhost:8000`.

## Safeguards

- Native prepared statements and exception-based PDO errors
- Server-side validation and output escaping
- CSRF tokens for writes and deletion through POST only
- Post/Redirect/Get after successful writes
- Decimal columns for monetary values, with cost and sale price mapped correctly
- Strict session-cookie defaults

## Scope and limitations

This remains an educational CRUD application. It has no user authentication or authorization, so deploy it only in a trusted local environment. Add an identity layer, role-based authorization, tests against a disposable database, audit logging, and production styling before broader use.
