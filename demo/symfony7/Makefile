# Makefile for Form Kit Bundle Demo - Symfony 7
COMPOSE_FILE := docker-compose.yml
COMPOSE := docker compose -f $(COMPOSE_FILE)
SERVICE_PHP := php

.PHONY: help up down restart build install shell logs cache-clear update-bundle ensure-up test test-coverage

help:
	@echo "Form Kit Bundle Demo - Symfony 7"
	@echo "  make up            Start container (http://localhost:$${PORT:-8007})"
	@echo "  make down          Stop container"
	@echo "  make restart       Restart container (docker compose restart)"
	@echo "  make build         Build image (no cache)"
	@echo "  make install       Install dependencies"
	@echo "  make update-bundle  Update bundle from path repo"
	@echo ""

up:
	@if [ ! -f .env ]; then cp .env.example .env 2>/dev/null || (echo "APP_ENV=dev" > .env; echo "APP_SECRET=change-me" >> .env); fi
	$(COMPOSE) up -d
	@sleep 5
	@$(COMPOSE) exec -T -e COMPOSER_MEMORY_LIMIT=-1 $(SERVICE_PHP) composer install --no-interaction
	@echo "App: http://localhost:$${PORT:-8007}"

down:
	$(COMPOSE) down

restart: ensure-up
	$(COMPOSE) restart

build:
	$(COMPOSE) build --no-cache

ensure-up:
	@if ! $(COMPOSE) exec -T $(SERVICE_PHP) true 2>/dev/null; then $(COMPOSE) up -d; sleep 5; $(COMPOSE) exec -T -e COMPOSER_MEMORY_LIMIT=-1 $(SERVICE_PHP) composer install --no-interaction; fi

install: ensure-up
	$(COMPOSE) exec -T -e COMPOSER_MEMORY_LIMIT=-1 $(SERVICE_PHP) composer install --no-interaction

shell: ensure-up
	$(COMPOSE) exec $(SERVICE_PHP) sh

logs:
	$(COMPOSE) logs -f

cache-clear: ensure-up
	$(COMPOSE) exec -T $(SERVICE_PHP) php bin/console cache:clear

update-bundle: ensure-up
	$(COMPOSE) exec -T $(SERVICE_PHP) composer update nowo-tech/form-kit-bundle symfony/asset --with-all-dependencies

test: ensure-up
	$(COMPOSE) exec -T $(SERVICE_PHP) vendor/bin/phpunit 2>/dev/null || true

test-coverage: ensure-up
	$(COMPOSE) exec -T $(SERVICE_PHP) vendor/bin/phpunit --coverage-text 2>/dev/null || true
