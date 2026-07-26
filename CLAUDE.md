# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

A MUD (text-based multiplayer game) server built on Symfony 8.1 / PHP 8.5, using Doctrine ORM 3 against MariaDB. Very early stage: `Controller/`, `Repository/`, and `Security/` are currently empty scaffolding.

## Dev environment (Docker Compose)

Services: `db` (MariaDB), `nginx`, `php` (php-fpm 8.5). All commands run through the Makefile, which wraps `docker compose`:

- `make init` — first-time setup: rebuild containers, composer install, drop+recreate DB, run migrations, load fixtures. Destructive — only use for a fresh environment.
- `make start` / `make stop` / `make restart` — routine container lifecycle.
- `make composer "<args>"` — run Composer inside the `php` container.
- `make console "<args>"` — run `bin/console` inside the `php` container.
- `make test` — runs migrations against the test env then PHPUnit.
- `make lint` — runs PHPStan static analysis.

**`make test` and the fixtures step in `make init` do not currently work.** PHPUnit and the Doctrine fixtures bundle are referenced by the Makefile but are not in `composer.json` (`require-dev` is empty), and there is no `tests/` directory yet. Don't assume test tooling exists — check before relying on it.

DB/env credentials for local dev are already filled in in `.env` (matches `docker-compose.yml`); there is no `.env.dist` template.

## Entity conventions

Entities in `src/Entity/` (Account, Character, Item, Room, RoomExit) follow a consistent style — match it for new entities:

- UUID primary keys via `ramsey/uuid-doctrine`, not auto-increment ints.
- PHP asymmetric visibility (`private(set)`) on properties instead of traditional private fields + getters/setters.
- Doctrine attributes (`#[ORM\...]`), not annotations or XML/YAML mapping.
- No constructor unless the entity initializes collections (e.g. `Room` initializes its `exits`/`items`/`characters` collections in its constructor).

Domain model: `Account` (the Symfony `UserInterface`) has many `Character`s and an active `currentCharacter`. Each `Character` has a `currentRoom`. `Room`s are connected by directed `RoomExit`s (`sourceRoom` → `targetRoom`, one-way). `Item`s live either in a `Room` or on a `Character`, never both.

## Auth

The `main` security firewall uses `access_token` authentication (bearer-token style, not form/session login) via `App\Security\AccessTokenHandler` — this class doesn't exist yet, so the security config won't compile as-is until it's added.

## Static analysis

PHPStan (level 5, with Doctrine and Symfony extensions) is configured in `phpstan.neon`. Run it with `docker compose exec php vendor/bin/phpstan analyse`. Pre-existing findings on the current entities are suppressed via `phpstan-baseline.neon` — don't add new errors to the baseline; fix them or, if a new pre-existing issue needs suppressing, regenerate the baseline explicitly with `--generate-baseline`.

No PHP-CS-Fixer/ECS/PHPCS is configured — code style/formatting isn't enforced yet.
