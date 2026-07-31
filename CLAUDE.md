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

`make test` runs migrations against the test env then the PHPUnit suite in `tests/` (`Unit/` — no DB, no kernel; `Functional/` — real DB via `KernelTestCase`, wrapped per-test in a rollback by `dama/doctrine-test-bundle`). The fixtures step in `make init` (`app:item-template:load` etc.) does work but there's no `doctrine/doctrine-fixtures-bundle`-style fixture system — seed data comes from a handful of `app:*` console commands instead.

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

## Telnet transport and coroutines (Swoole)

`app:telnet:serve` (`src/Command/TelnetServerCommand.php`) runs on `ext-swoole`, not ReactPHP: a single-process `Swoole\Coroutine\Server` where **every connection gets its own coroutine**, so a slow Doctrine query on one connection no longer blocks every other player (verified empirically: two concurrent `pg_sleep(2)` calls finish in ~2s total, not ~4s). Single process is deliberate — `worker_num > 1` (`Swoole\Server`) would run workers as separate OS processes, fragmenting `GameWorld`'s in-memory shared state (players in "the same" room on different workers would never see each other) without adding a whole IPC layer, which this app doesn't have.

This has real consequences for how Doctrine and shared game state are used — don't "simplify" the following without understanding why they're this way:

- **`EntityManagerInterface` is aliased to `App\Doctrine\EntityManagerProxy`** (`config/services.yaml`), which hands out a *different* real `EntityManager` per coroutine (`App\Doctrine\CoroutineEntityManagerRegistry`, keyed on `Swoole\Coroutine::getContext()`) — a PDO handle can't be shared by coroutines that might each be mid-query when the other yields. Outside a coroutine (`bin/console`, PHPUnit) it transparently falls back to the real singleton EM, so none of this is visible to tests or one-off commands. One EM is opened per telnet *connection* (not per command) and closed when the connection ends (`TelnetConnectionHandler::run()`).
- **`RoomRepository`/`ItemTemplateRepository` are plain `EntityManagerInterface`-backed classes, not `ServiceEntityRepository`.** Doctrine bundle's `ContainerRepositoryFactory` resolves a `ServiceEntityRepository` straight from the container, *ignoring* whichever EntityManager it was asked for — that would silently defeat the per-coroutine scoping above. Don't switch them back.
- **`GameWorld`/`RoomInstance`/`AuthWorld` hold cross-connection state in `SplObjectStorage`s.** Any loop over that state is only safe if nothing inside the loop body can yield (a coroutine only actually switches at a real I/O call) — `RoomInstance::broadcast()` snapshots (`iterator_to_array`) before iterating for exactly this reason. `RoomInstance` also never holds a long-lived `Room` *entity object* across coroutines (it resolves a fresh `getReference()` from the *current* coroutine's EM on each use) — an earlier version cached the `Room` object from server boot and broke with `A new entity was found through the relationship... not configured to cascade persist` the moment a player-coroutine's own EntityManager tried to flush a `Character` pointing at that foreign, boot-coroutine-owned object.
- **Two different players can genuinely race for the same item now** (they couldn't under the old single-threaded ReactPHP transport). `ItemService::addItemToInventory()` re-reads the row under `LockMode::PESSIMISTIC_WRITE` inside a transaction before deciding whether the item is still up for grabs — no extra `#[ORM\Version]` column needed, just `find($id, $lockMode)` inside `wrapInTransaction()`. If you add a new mutation that two *different* characters could plausibly race for, use the same pattern; mutations that only the item's current, single owner can trigger (equip/unequip/drop) don't need it, since one telnet connection is one coroutine and a player's own commands are strictly sequential.

## Auth

This is a telnet-only app — no HTTP request ever reaches it, so `symfony/security-bundle`'s firewall/provider/access_control machinery is unused; `config/packages/security.yaml` only keeps `password_hashers:` (used by `Login`/`Register` via `UserPasswordHasherInterface`, invoked manually from telnet prompt callbacks) and a minimal `firewalls.main` placeholder required by the bundle's config schema.

## Static analysis

PHPStan (level 5, with Doctrine and Symfony extensions) is configured in `phpstan.neon`. Run it with `docker compose exec php vendor/bin/phpstan analyse`. Pre-existing findings on the current entities are suppressed via `phpstan-baseline.neon` — don't add new errors to the baseline; fix them or, if a new pre-existing issue needs suppressing, regenerate the baseline explicitly with `--generate-baseline`.

No PHP-CS-Fixer/ECS/PHPCS is configured — code style/formatting isn't enforced yet.
