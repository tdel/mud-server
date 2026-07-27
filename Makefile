.PHONY: help
.DEFAULT_GOAL = help

DOCKER_COMPOSE=@docker compose
DOCKER_COMPOSE_EXEC=$(DOCKER_COMPOSE) exec
PHP_DOCKER_COMPOSE_EXEC=$(DOCKER_COMPOSE_EXEC) php
SYMFONY_CONSOLE=$(PHP_DOCKER_COMPOSE_EXEC) bin/console

## —— Docker 🐳  ———————————————————————————————————————————————————————————————
init: ## Full rebuild (cached) && doctrine:database drop + migrate
	$(DOCKER_COMPOSE) pull
	$(DOCKER_COMPOSE) build
	$(DOCKER_COMPOSE) up -d
	$(MAKE) composer install
	$(PHP_DOCKER_COMPOSE_EXEC) bin/console doctrine:database:drop --force --connection --if-exists
	$(PHP_DOCKER_COMPOSE_EXEC) bin/console doctrine:database:create
	$(PHP_DOCKER_COMPOSE_EXEC) bin/console doctrine:migrations:migrate --no-interaction
	$(PHP_DOCKER_COMPOSE_EXEC) bin/console app:item-template:load

rebuild: ## Force a full image rebuild without cache
	$(DOCKER_COMPOSE) build --no-cache php
	$(DOCKER_COMPOSE) up -d

start:	## Start Docker containers && npm install && assets-build && doctrine migrate
	$(DOCKER_COMPOSE) up -d
	@echo "Running composer install"
	$(MAKE) composer install
	#$(PHP_DOCKER_COMPOSE_EXEC) bin/console doctrine:migration:migrate --no-interaction

stop: ## Stop Docker containers
	$(DOCKER_COMPOSE) stop

restart: stop start

## —— Symfony 🎶 ———————————————————————————————————————————————————————————————
composer:	## Run Composer command eg: make composer "require xxx"
	@echo "Running composer with arguments: $(filter-out $@,$(MAKECMDGOALS))"
	$(PHP_DOCKER_COMPOSE_EXEC) bin/composer.phar $(filter-out $@,$(MAKECMDGOALS))

console: ## Run Symfony console eg: make console "ca:c"
	$(PHP_DOCKER_COMPOSE_EXEC) bin/console $(filter-out $@,$(MAKECMDGOALS))

test: ## Execute tests
	$(PHP_DOCKER_COMPOSE_EXEC) bin/console doctrine:migration:migrate --no-interaction --env=test
	$(PHP_DOCKER_COMPOSE_EXEC) vendor/bin/phpunit $(filter-out $@,$(MAKECMDGOALS))

lint: ## Run PHPStan static analysis
	$(PHP_DOCKER_COMPOSE_EXEC) vendor/bin/phpstan analyse

telnet: ## Start the telnet MUD server (foreground)
	$(PHP_DOCKER_COMPOSE_EXEC) bin/console app:telnet:serve
%:
	@:

## —— Others 🛠️️ ———————————————————————————————————————————————————————————————
help: ## Command List
	@grep -E '(^[a-zA-Z0-9_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}{printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'

# Perl Colors, with fallback if tput command not available
GREEN  := $(shell command -v tput >/dev/null 2>&1 && tput -Txterm setaf 2 || echo "")
BLUE   := $(shell command -v tput >/dev/null 2>&1 && tput -Txterm setaf 4 || echo "")
WHITE  := $(shell command -v tput >/dev/null 2>&1 && tput -Txterm setaf 7 || echo "")
YELLOW := $(shell command -v tput >/dev/null 2>&1 && tput -Txterm setaf 3 || echo "")
RED    := $(shell command -v tput >/dev/null 2>&1 && tput -Txterm setaf 1 || echo "")
RESET  := $(shell command -v tput >/dev/null 2>&1 && tput -Txterm sgr0 || echo "")
