# mud-server

A text-based multiplayer game (MUD) server, built on Symfony and reachable over plain telnet. Connect, log in (or create an account on the fly), pick a character, and explore rooms connected by exits — no client needed beyond a telnet program.

## What it does

- Players connect over raw TCP/telnet and either `register` a new account or `login` to an existing one.
- Each account can have several characters; pick one with `character-select` to enter the world.
- Rooms are connected by one-way exits. Players in the same room can `look` around and `say` things to each other.
- Everything is persisted to a database (accounts, characters, rooms, items) via Doctrine/PostgreSQL.

## Requirements

- Docker and Docker Compose (everything else — PHP, PostgreSQL, nginx — runs in containers).

## Getting started

First-time setup (rebuilds the containers, installs dependencies, creates and migrates the database):

```
make init
```

Day-to-day, just start/stop the containers:

```
make start
make stop
```

## Create your first room

The world needs at least one room, and one of them must be marked as the "starting room" — that's where every new character begins.

```
make console "app:room:create --name='Village Square' --description='A small paved square.' --starting"
```

Drop the `--starting` flag for any room after the first one. If you omit `--name`/`--description`, the command will prompt you for them interactively.

## Run the server

```
make telnet
```

This starts the telnet server in the foreground (`Ctrl+C` to stop). By default it listens on `0.0.0.0:4000` — configurable via the `TELNET_HOST`/`TELNET_PORT` variables in `.env`.

Connect from another terminal:

```
telnet localhost 4000
```

## Commands

| Command | Available when | Description |
|---|---|---|
| `register <name>` | not logged in | Create a new account and log in with it |
| `login <name>` | not logged in | Log in to an existing account |
| `characters-list` | logged in | List your characters |
| `character-create <name>` | logged in | Create a new character |
| `character-select <name>` | logged in | Start playing as this character |
| `character-delete <name>` | logged in | Delete a character |
| `logout` | logged in | Log out back to the login prompt |
| `look` | in game | Describe the current room |
| `say <message>` | in game | Say something to everyone in the room |
| `quit` | not logged in | Close the connection |

## Other useful commands

```
make composer "<args>"   # run Composer inside the php container
make console "<args>"    # run bin/console inside the php container
make lint                # run PHPStan static analysis
```
