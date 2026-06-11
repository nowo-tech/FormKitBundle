# Makefile for Form Kit Bundle demos
# Standard targets: up-X, down-X, ... delegate to $(MAKE) -C <demo> <target>

# Demos: Symfony 7.4 and 8.x (bundle 2.x).
DEMOS := symfony7 symfony8

# Do not list up-symfony7-style names in .PHONY: GNU Make would treat them as empty
# phony targets and skip pattern rules (up-%, assets-%, …).
.PHONY: help clean test-all test-coverage-all verify-all release-verify assets-all
.PHONY: up down restart build install update-bundle test test-coverage shell logs verify

help:
	@echo "Form Kit Bundle - Demos"
	@echo ""
	@echo "Usage: make <target>"
	@echo ""
	@echo "Per-demo targets (Demos: $(DEMOS)):"
	@echo "  up-<demo>           Start demo containers (e.g. make up-symfony8)"
	@echo "  down-<demo>         Stop demo containers"
	@echo "  restart-<demo>      Restart demo containers"
	@echo "  build-<demo>        Rebuild image (no cache)"
	@echo "  install-<demo>      Install dependencies in container"
	@echo "  update-bundle-<demo> Update bundle from path repo"
	@echo "  shell-<demo>        Open shell in PHP container"
	@echo "  logs-<demo>         Show container logs"
	@echo "  test-<demo>         Run tests"
	@echo "  test-coverage-<demo> Run tests with coverage"
	@echo "  verify-<demo>       Ensure demo up and check HTTP 200"
	@echo "  assets-<demo>       pnpm install + Vite build in that demo (host Node; needs pnpm)"
	@echo "  assets-all          Run assets for active demos ($(DEMOS))"
	@echo ""
	@echo "Generic (use DEMO=<name>): make up DEMO=symfony8"
	@echo "  up down restart build install update-bundle test test-coverage shell logs verify"
	@echo ""
	@echo "Aggregated:"
	@echo "  test-all            Run tests for active demos ($(DEMOS))"
	@echo "  test-coverage-all   Run tests with coverage for active demos"
	@echo "  verify-all         Verify active demos (up + HTTP 200)"
	@echo "  release-verify     For each active demo: up → healthcheck → down (for release-check)"
	@echo "  clean              Remove vendor, var, cache from all demos"
	@echo ""

# Delegate to each demo's Makefile
up-%:
	@$(MAKE) -C $* up

down-%:
	@$(MAKE) -C $* down

restart-%:
	@$(MAKE) -C $* restart

build-%:
	@$(MAKE) -C $* build

install-%:
	@$(MAKE) -C $* install

update-bundle-%:
	@$(MAKE) -C $* update-bundle

shell-%:
	@$(MAKE) -C $* shell

logs-%:
	@$(MAKE) -C $* logs

test-%:
	@$(MAKE) -C $* test

test-coverage-%:
	@$(MAKE) -C $* test-coverage

verify-%:
	@$(MAKE) -C $* verify

assets-%:
	@$(MAKE) -C $* assets

assets-all:
	@for d in $(DEMOS); do $(MAKE) assets-$$d || exit 1; done
	@echo "assets-all: Vite builds done for $(DEMOS)."

# Generic commands (require DEMO=<name>)
up:
	@if [ -z "$(DEMO)" ]; then echo "Error: use make up DEMO=symfony7"; exit 1; fi
	@$(MAKE) up-$(DEMO)

down:
	@if [ -z "$(DEMO)" ]; then echo "Error: use make down DEMO=symfony7"; exit 1; fi
	@$(MAKE) down-$(DEMO)

restart:
	@if [ -z "$(DEMO)" ]; then echo "Error: use make restart DEMO=symfony7"; exit 1; fi
	@$(MAKE) restart-$(DEMO)

build:
	@if [ -z "$(DEMO)" ]; then echo "Error: use make build DEMO=symfony7"; exit 1; fi
	@$(MAKE) build-$(DEMO)

install:
	@if [ -z "$(DEMO)" ]; then echo "Error: use make install DEMO=symfony7"; exit 1; fi
	@$(MAKE) install-$(DEMO)

update-bundle:
	@if [ -z "$(DEMO)" ]; then echo "Error: use make update-bundle DEMO=symfony7"; exit 1; fi
	@$(MAKE) update-bundle-$(DEMO)

test:
	@if [ -z "$(DEMO)" ]; then echo "Error: use make test DEMO=symfony7"; exit 1; fi
	@$(MAKE) test-$(DEMO)

test-coverage:
	@if [ -z "$(DEMO)" ]; then echo "Error: use make test-coverage DEMO=symfony7"; exit 1; fi
	@$(MAKE) test-coverage-$(DEMO)

shell:
	@if [ -z "$(DEMO)" ]; then echo "Error: use make shell DEMO=symfony7"; exit 1; fi
	@$(MAKE) shell-$(DEMO)

logs:
	@if [ -z "$(DEMO)" ]; then echo "Error: use make logs DEMO=symfony7"; exit 1; fi
	@$(MAKE) logs-$(DEMO)

verify:
	@if [ -z "$(DEMO)" ]; then echo "Error: use make verify DEMO=symfony7"; exit 1; fi
	@$(MAKE) verify-$(DEMO)

test-all:
	@for d in $(DEMOS); do $(MAKE) test-$$d || exit 1; done
	@echo "All demos passed tests."

test-coverage-all:
	@for d in $(DEMOS); do $(MAKE) test-coverage-$$d || exit 1; done
	@echo "All demos coverage done."

verify-all:
	@for d in $(DEMOS); do $(MAKE) verify-$$d; $(MAKE) down-$$d || true; done
	@echo "Verify-all done."

release-check: test-coverage-all release-verify
	@echo "All demos passed release-check."

release-verify:
	@for d in $(DEMOS); do \
		port=$$(grep '^PORT=' $$d/.env 2>/dev/null | cut -d= -f2 | tr -d '\r'); \
		[ -z "$$port" ] && port=$$(case $$d in symfony7) echo 8007;; symfony8) echo 8008;; *) echo 8000;; esac); \
		echo "Release verify: $$d (port $$port)..."; \
		$(MAKE) up-$$d; sleep 15; \
		code=$$(curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1:$$port 2>/dev/null || echo "000"); \
		$(MAKE) down-$$d 2>/dev/null || true; \
		if [ "$$code" != "200" ]; then echo "$$d healthcheck failed (HTTP $$code)"; exit 1; fi; \
		echo "$$d OK"; \
	done
	@echo "All demos passed release-verify."

clean:
	@for d in $(DEMOS); do rm -rf $$d/vendor $$d/var $$d/.phpunit.cache; echo "Cleaned $$d"; done
	@echo "Clean done."

# REQ-MAKE-008: update-deps-all (REQ-MAKE-008)
BUNDLE_ROOT := $(abspath $(dir $(lastword $(MAKEFILE_LIST)))/..)
include $(abspath $(dir $(lastword $(MAKEFILE_LIST)))/../../.scripts/Makefile.demo-aggregate-update-deps.mk
