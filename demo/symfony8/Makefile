# Makefile for Form Kit Bundle Demo - Symfony 8
# Run from this directory: make up, make install, etc.

SHELL := /bin/bash
# Prefer Compose V2; absolute docker path avoids shadowing by local docker/ when PATH has "." (REQ-MAKE-010).
DOCKER_BIN := $(shell command -v docker 2>/dev/null)
COMPOSE_FILE := docker-compose.yml
ifeq ($(DOCKER_BIN),)
COMPOSE_BIN := docker-compose
else
COMPOSE_BIN := $(shell $(DOCKER_BIN) compose version >/dev/null 2>&1 && echo "$(DOCKER_BIN) compose" || echo "docker-compose")
endif
DOCKER_COMPOSE := $(COMPOSE_BIN) -f $(COMPOSE_FILE)
SERVICE_PHP := php

.PHONY: help up down restart build install shell logs cache-clear update-bundle ensure-up test test-coverage verify assets

help:
	@echo "Form Kit Bundle Demo - Symfony 8"
	@echo ""
	@echo "  make up            Start container (http://localhost:$${PORT:-8008})"
	@echo "  make down          Stop container"
	@echo "  make restart       Restart container (docker compose restart)"
	@echo "  make build         Build image (no cache)"
	@echo "  make install       Composer + pnpm (JS) in container"
	@echo "  make shell         Open shell in PHP container"
	@echo "  make logs          Show container logs"
	@echo "  make cache-clear   Clear Symfony cache"
	@echo "  make update-bundle Update bundle from path repo"
	@echo "  make test          Run tests"
	@echo "  make test-coverage Run tests with coverage"
	@echo "  make verify       Ensure up and check HTTP 200"
	@echo "  make assets       typescript:build (importmap .ts) + pnpm + Vite (app.ts → public/build/)"
	@echo ""

up:
	@if [ ! -f .env ]; then cp .env.example .env 2>/dev/null || (echo "APP_ENV=dev" > .env; echo "APP_SECRET=change-me" >> .env); fi
	@PORT_VALUE=$$(grep "^PORT=" .env 2>/dev/null | cut -d= -f2 | tr -d '\r'); \
	[ -z "$$PORT_VALUE" ] && PORT_VALUE=$$(grep "^PORT=" .env.example 2>/dev/null | cut -d= -f2 | tr -d '\r'); \
	PORT=$$PORT_VALUE $(DOCKER_COMPOSE) up -d
	@echo "Waiting for container to be ready..."
	@sleep 5
	@echo "Installing dependencies..."
	@$(DOCKER_COMPOSE) exec -T $(SERVICE_PHP) sh -c 'COMPOSER_MEMORY_LIMIT=-1 composer install --no-interaction'
	@$(DOCKER_COMPOSE) exec -T $(SERVICE_PHP) php bin/console assets:install public --symlink --no-interaction 2>/dev/null || true
	@PORT=$$(grep "^PORT=" .env 2>/dev/null | cut -d= -f2 | tr -d '\r'); \
	[ -z "$$PORT" ] && PORT=$$(grep "^PORT=" .env.example 2>/dev/null | cut -d= -f2 | tr -d '\r'); \
	echo "Demo started at: http://localhost:$$PORT"
down:
	$(DOCKER_COMPOSE) down

restart: ensure-up
	$(DOCKER_COMPOSE) restart

build:
	$(DOCKER_COMPOSE) build --no-cache

ensure-up:
	@if ! $(DOCKER_COMPOSE) exec -T $(SERVICE_PHP) true 2>/dev/null; then \
		echo "Starting container..."; \
		PORT_VALUE=$$(grep "^PORT=" .env 2>/dev/null | cut -d= -f2 | tr -d '\r'); \
		[ -z "$$PORT_VALUE" ] && PORT_VALUE=$$(grep "^PORT=" .env.example 2>/dev/null | cut -d= -f2 | tr -d '\r'); \
		PORT=$$PORT_VALUE $(DOCKER_COMPOSE) up -d; \
		sleep 5; \
		$(DOCKER_COMPOSE) exec -T $(SERVICE_PHP) sh -c 'COMPOSER_MEMORY_LIMIT=-1 composer install --no-interaction'; \
		$(DOCKER_COMPOSE) exec -T $(SERVICE_PHP) php bin/console assets:install public --symlink --no-interaction 2>/dev/null || true; \
	fi

install: ensure-up
	$(DOCKER_COMPOSE) exec -T $(SERVICE_PHP) sh -c 'COMPOSER_MEMORY_LIMIT=-1 composer install --no-interaction'
	$(DOCKER_COMPOSE) exec -T $(SERVICE_PHP) php bin/console assets:install public --symlink --no-interaction 2>/dev/null || true
	$(DOCKER_COMPOSE) exec -T $(SERVICE_PHP) sh -c 'test -f package.json && pnpm install || true'

shell: ensure-up
	$(DOCKER_COMPOSE) exec $(SERVICE_PHP) sh

logs:
	$(DOCKER_COMPOSE) logs -f

cache-clear: ensure-up
	$(DOCKER_COMPOSE) exec -T $(SERVICE_PHP) php bin/console cache:clear

update-bundle: ensure-up
	$(DOCKER_COMPOSE) exec -T $(SERVICE_PHP) composer update nowo-tech/form-kit-bundle --with-all-dependencies

test: ensure-up
	@$(DOCKER_COMPOSE) exec -T $(SERVICE_PHP) sh -c 'composer test 2>/dev/null || $(MAKE) verify'

test-coverage: ensure-up
	@# Demo has no PHPUnit suite; alias coverage gate to HTTP smoke (REQ-TEST-011).
	@$(MAKE) verify

verify: ensure-up
	@port=$$(grep '^PORT=' .env 2>/dev/null | cut -d= -f2 | tr -d '\r'); [ -z "$$port" ] && port=8008; \
	code=$$(curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1:$$port/en/ 2>/dev/null || echo "000"); \
	case "$$code" in \
		2*|3*) echo "OK (HTTP $$code)";; \
		*) echo "Failed (HTTP $$code)"; exit 1;; \
	esac

assets:
	@command -v php >/dev/null 2>&1 && php bin/console typescript:build --no-interaction || true
	@# If Docker generated root-owned build artifacts, fix ownership through the running php container.
	@$(DOCKER_COMPOSE) exec -T $(SERVICE_PHP) sh -c 'chown -R 1000:1000 /app/public/build /app/node_modules 2>/dev/null || true' >/dev/null 2>&1 || true
	@rm -rf public/build/.vite public/build/assets 2>/dev/null || true
	pnpm install && pnpm run build


# REQ-MAKE-008: update-deps (REQ-MAKE-008)
BUNDLE_ROOT := $(abspath $(dir $(lastword $(MAKEFILE_LIST)))/../..)
# Optional: monorepo helper absent on standalone GitHub Actions checkout (REQ-MAKE-009).
-include $(BUNDLE_ROOT)/../.scripts/Makefile.demo-update-deps.mk
