# Makefile for Form Kit Bundle
# Development and QA targets run inside the Docker container
#
COMPOSE_FILE := docker-compose.yml
COMPOSE_PROJECT_DIR := $(CURDIR)
# Prefer Compose V2 plugin (GitHub Actions / modern Docker Desktop); fall back to docker-compose V1 (REQ-MAKE-010).
COMPOSE_BIN := $(shell docker compose version >/dev/null 2>&1 && echo "docker compose" || echo "docker-compose")
COMPOSE := $(COMPOSE_BIN) -f $(COMPOSE_FILE) --project-directory $(COMPOSE_PROJECT_DIR)
SERVICE_PHP := php
RUN := $(COMPOSE) exec -T $(SERVICE_PHP)

COMPOSER ?= composer

.PHONY: help install test test-coverage coverage-php-percent coverage-check test-ts coverage-ts-percent cs-check cs-fix qa clean ensure-up update validate assets assets-test release-check release-check-demos demo-smoke composer-sync rector rector-dry phpstan check-no-cursor-coauthor check-open-prs strip-cursor-coauthor-from-history setup-hooks check-twig-extra
.PHONY: demo-up-symfony8
.PHONY: up down down-dev up-symfony8 build shell demo-install demo-down

help:
	@echo "Form Kit Bundle - Development Commands (Docker)"
	@echo ""
	@echo "Usage: make <target>"
	@echo ""
	@echo "Targets:"
	@echo "  up             Start root container ($(COMPOSE) at bundle root)"
	@echo "  down           Stop root container"
	@echo "  build          Rebuild root Docker image (no cache)"
	@echo "  shell          Open shell in root container"
	@echo "  install        Install Composer dependencies"
	@echo "  assets         Build frontend assets with Vite (TS)"
	@echo "  test-ts        Run Vitest with coverage + print global TS %% (min of S/B/F/L)"
	@echo "  assets-test    Alias of test-ts (Vitest + coverage summary)"
	@echo "  test           Run PHPUnit tests"
	@echo "  test-coverage  Run PHPUnit with coverage + print global PHP %% (Lines)"
	@echo "  coverage-check Run coverage and fail if Lines < 99%% (REQ-TEST-003)"
	@echo "  cs-check       Check code style (PHP-CS-Fixer)"
	@echo "  cs-fix         Fix code style"
	@echo "  rector         Apply Rector refactoring"
	@echo "  rector-dry     Run Rector in dry-run mode"
	@echo "  phpstan        Run PHPStan static analysis"
	@echo "  qa             Run all QA (cs-check + test)"
	@echo "  release-check  Pre-release: git hygiene, open PRs, cs-fix, cs-check, rector-dry, phpstan, coverage-check, test-ts, demo healthchecks"
	@echo "  demo-smoke     Boot demos and assert HTTP 200 (REQ-TEST-011)"
	@echo "  setup-hooks    Install local git hooks (.githooks; REQ-GIT-001)"
	@echo "  check-no-cursor-coauthor  Fail if history has Cursor co-author trailers"
	@echo "  check-open-prs Fail if unresolved open GitHub PRs (REQ-REL-003)"
	@echo "  composer-sync  Validate composer.json and align composer.lock (no install)"
	@echo "  clean          Remove vendor, cache, coverage"
	@echo "  update         Update composer.lock"
	@echo "  validate       Run composer validate --strict"
	@echo ""
	@echo "Bundle-specific:"
	@echo "  down-dev       Stop root container (alias for down)"
	@echo ""
	@echo "Demos:"
	@echo "  demo-install       Install Composer dependencies in demo/symfony8"
	@echo "  demo-up-symfony8   Install deps in demo/symfony8 (Symfony 8.1)"
	@echo "  up-symfony8        Start demo symfony8 (http://localhost:8008)"
	@echo "  demo-down          Stop demo containers"
	@echo ""

ensure-up:
	@if ! $(COMPOSE) exec -T $(SERVICE_PHP) true 2>/dev/null; then \
		echo "Container not running. Starting docker compose..."; \
		$(COMPOSE) up -d; \
		sleep 2; \
	fi

install: ensure-up
	$(RUN) composer install

# Run tests (no -T so TTY is allocated and PHPUnit can show colors in console)
test: install
	$(COMPOSE) exec $(SERVICE_PHP) composer test

# Run tests with coverage (no -T so coverage is shown in console with colors)
test-coverage: install
	$(COMPOSE) exec $(SERVICE_PHP) composer test-coverage | tee coverage-php.txt
	sh ./.scripts/php-coverage-percent.sh coverage-php.txt

coverage-check: ensure-up
	@$(COMPOSE) exec -T $(SERVICE_PHP) sh -lc 'composer test-coverage | tee coverage-php.txt'
	@./.scripts/coverage-fail-under.sh coverage-php.txt 99

cs-check: install
	$(RUN) composer cs-check

cs-fix: install
	$(RUN) composer cs-fix

rector: install
	$(RUN) composer rector

rector-dry: install
	$(RUN) composer rector-dry

phpstan: install
	$(RUN) composer phpstan

qa: install
	$(RUN) composer qa


check-twig-extra:
	@chmod +x .scripts/check-twig-extra.sh
	@./.scripts/check-twig-extra.sh
release-check: check-no-cursor-coauthor check-open-prs check-twig-extra ensure-up composer-sync cs-fix cs-check rector-dry phpstan coverage-check test-ts release-check-demos

release-check-demos:
	@$(MAKE) -C demo release-check

# REQ-TEST-011: boot demos and assert HTTP 200
demo-smoke:
	@if [ -f demo/Makefile ]; then $(MAKE) -C demo release-verify; else echo "No demo/Makefile"; exit 1; fi

composer-sync: ensure-up
	$(RUN) composer validate --strict
	$(RUN) composer update --no-install

clean: ensure-up
	$(RUN) sh -c 'rm -rf vendor .phpunit.cache coverage coverage.xml .php-cs-fixer.cache'

update: ensure-up
	$(RUN) composer update

validate: ensure-up
	$(RUN) composer validate --strict

assets:
	@if [ ! -f package.json ]; then \
		echo "No package.json found in bundle root."; \
		exit 1; \
	fi
	@if ! command -v pnpm >/dev/null 2>&1; then \
		echo "pnpm is required to build frontend assets."; \
		exit 1; \
	fi
	@echo "Installing frontend dependencies (including dev)..."
	@pnpm install
	@echo "Building Vite assets..."
	@pnpm run build
	@echo "Frontend assets built in src/Resources/public"

# Vitest + coverage; tee output then show global % (same contract as TwigInspectorBundle)
test-ts:
	@if [ ! -f package.json ]; then \
		echo "No package.json found in bundle root."; \
		exit 1; \
	fi
	@if ! command -v pnpm >/dev/null 2>&1; then \
		echo "pnpm is required to run frontend tests."; \
		exit 1; \
	fi
	@echo "Installing frontend dependencies (including dev)..."
	@pnpm install
	@echo "Running frontend tests (Vitest) with coverage..."
	@pnpm run test:coverage | tee coverage-ts.txt
	@sh ./.scripts/ts-coverage-percent.sh coverage-ts.txt
	@echo "✅ TypeScript tests done!"

assets-test: test-ts

demo-up-symfony8:
	@echo "Installing demo symfony8..."
	cd demo/symfony8 && $(COMPOSER) install --no-interaction
	@if command -v pnpm >/dev/null 2>&1; then \
		cd demo/symfony8 && pnpm install && pnpm run build; \
	else \
		echo "pnpm not found; skip demo Vite build (cd demo/symfony8 && pnpm install && pnpm run build)."; \
	fi
	@echo "✅ demo/symfony8 ready"

# Root $(COMPOSE) (bundle dev: install, test, cs-check, etc.)
up:
	$(COMPOSE) build
	$(COMPOSE) up -d
	@echo "Installing dependencies..."
	@sleep 2
	$(RUN) composer install --no-interaction
	@echo "✅ Root container ready!"

down:
	$(COMPOSE) down

down-dev: down

shell:
	$(COMPOSE) exec $(SERVICE_PHP) sh

build:
	$(COMPOSE) build --no-cache

# Demos with Docker
up-symfony8:
	$(MAKE) -C demo/symfony8 up

demo-down:
	$(MAKE) -C demo/symfony8 down

demo-install:
	$(MAKE) -C demo/symfony8 install

setup-hooks:
	@chmod +x .githooks/pre-commit 2>/dev/null || true
	@chmod +x .githooks/commit-msg 2>/dev/null || true
	@git config core.hooksPath .githooks
	@echo "✅ Git hooks installed (.githooks — includes commit-msg for REQ-GIT-001)."

check-no-cursor-coauthor:
	@chmod +x .scripts/check-no-cursor-coauthor.sh
	@./.scripts/check-no-cursor-coauthor.sh HEAD

check-open-prs:
	@chmod +x .scripts/check-open-prs.sh
	@GH_REPO=nowo-tech/FormKitBundle ./.scripts/check-open-prs.sh

strip-cursor-coauthor-from-history:
	@chmod +x .scripts/strip-cursor-coauthor-from-history.sh
	@./.scripts/strip-cursor-coauthor-from-history.sh main

# REQ-MAKE-008: update-deps (REQ-MAKE-008)
BUNDLE_ROOT := $(abspath $(dir $(lastword $(MAKEFILE_LIST))))
# Optional: monorepo helper absent on standalone GitHub Actions checkout (REQ-MAKE-009).
-include $(BUNDLE_ROOT)/../.scripts/Makefile.update-deps.mk

twig-lint: ensure-up
	@$(COMPOSE) exec -T $(SERVICE_PHP) composer twig:lint || $(COMPOSE) exec -T $(SERVICE_PHP) ./vendor/bin/twig-cs-fixer lint --config=.twig-cs-fixer.php
