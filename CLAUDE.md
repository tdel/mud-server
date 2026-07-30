# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

A MUD (text-based multiplayer game) server built on Symfony 8.1 / PHP 8.5, using Doctrine ORM 3 against PostgreSQL. Very early stage: `Controller/` is currently empty scaffolding (just a `.gitignore`), and `Security/` doesn't exist as a directory yet — see Auth below. `Repository/` already holds a few concrete repositories (`ItemTemplateRepository`, `RoomRepository`).

## Game rules

The game's mechanics are grounded in Dungeons & Dragons 5th Edition (D&D 5e) — ability scores, races, classes, the d20 dice-resolution system, etc. Rules are ported over incrementally as the game needs them, not all upfront; when implementing a new rule, match the 5e SRD (System Reference Document, freely available online) rather than inventing homebrew mechanics, so the ruleset stays internally consistent as it grows.

Implemented so far: the six ability scores on `Character` (`strength`, `dexterity`, `constitution`, `intelligence`, `wisdom`, `charisma`, default 10), plus separate current/max health and mana pools.

## Dev environment (Docker Compose)

Services: `db` (PostgreSQL), `php` (CLI sandbox for composer/`bin/console`/tests — no HTTP server, nothing listens on it), `telnet` (runs `app:telnet:serve`, the actual game server, with `restart: unless-stopped`). All commands run through the Makefile, which wraps `docker compose`:

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

Domain model: `Account` (the Symfony `UserInterface`) has many `Character`s and an active `currentCharacter`. Each `Character` has a `currentRoom`. `Room`s are connected by directed `RoomExit`s (`sourceRoom` → `targetRoom`, one-way). `Item`s live either in a `Room` or on a `Character`, never both; a carried `Item` may additionally be equipped in an `EquipmentSlot`.

## Game domain services

`App\Game\ItemService` (`src/Game/ItemService.php`) is the single entry point for reading and mutating a character's items — the bag and the equipped slots alike. It's a regular Symfony service (autowired like everything under `src/`, no special config), owns `EntityManagerInterface`, and flushes on every mutation, so callers never flush themselves. New code touching a character's inventory (take/drop/equip/unequip, and any future crafting/loot/trade flow) should go through it rather than querying `Item` directly via the generic Doctrine repository — this is what the `Take`/`Drop`/`Equip`/`Unequip`/`Inventory` actions in `src/Network/In/Ingame/` do today.

## Auth

This is a telnet-only app — no HTTP request ever reaches it, so `symfony/security-bundle`'s firewall/provider/access_control machinery is unused; `config/packages/security.yaml` only keeps `password_hashers:` (used by `Login`/`Register` via `UserPasswordHasherInterface`, invoked manually from telnet prompt callbacks) and a minimal `firewalls.main` placeholder required by the bundle's config schema.

## Static analysis

PHPStan (level 5, with Doctrine and Symfony extensions) is configured in `phpstan.neon`. Run it with `docker compose exec php vendor/bin/phpstan analyse`. Pre-existing findings on the current entities are suppressed via `phpstan-baseline.neon` — don't add new errors to the baseline; fix them or, if a new pre-existing issue needs suppressing, regenerate the baseline explicitly with `--generate-baseline`.

No PHP-CS-Fixer/ECS/PHPCS is configured — code style/formatting isn't enforced yet.
