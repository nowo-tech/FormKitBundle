# Makefile to manage Form Kit Bundle demos
# From bundle root: make demo-up-symfony8 / make up-symfony8

.PHONY: help up-all clean-all release-verify
.PHONY: up-symfony6 up-symfony7 up-symfony8
.PHONY: docker-up-symfony6 docker-up-symfony7 docker-up-symfony8 docker-down-symfony6 docker-down-symfony7 docker-down-symfony8
.PHONY: docker-restart-symfony6 docker-restart-symfony7 docker-restart-symfony8 docker-build-symfony6 docker-build-symfony7 docker-build-symfony8
.PHONY: docker-install-symfony6 docker-install-symfony7 docker-install-symfony8 docker-update-bundle-symfony6 docker-update-bundle-symfony7 docker-update-bundle-symfony8

DEMOS := symfony6 symfony7 symfony8

help:
	@echo "Form Kit Bundle - Demos"
	@echo "Demos: $(DEMOS)"
	@echo ""
	@echo "Without Docker:"
	@echo "  up-symfony6  Install in demo/symfony6"
	@echo "  up-symfony7  Install in demo/symfony7"
	@echo "  up-symfony8  Install in demo/symfony8"
	@echo ""
	@echo "With Docker (from demo/ folder, e.g. cd symfony8 && make up):"
	@echo "  docker-up-symfony6   Start container demo/symfony6"
	@echo "  docker-up-symfony7  Start container demo/symfony7"
	@echo "  docker-up-symfony8  Start container demo/symfony8"
	@echo "  docker-restart-symfony6  Restart container"
	@echo "  docker-restart-symfony7  Restart container"
	@echo "  docker-restart-symfony8  Restart container"
	@echo "  docker-build-symfony6    Rebuild image (no cache)"
	@echo "  docker-build-symfony7   Rebuild image (no cache)"
	@echo "  docker-build-symfony8   Rebuild image (no cache)"
	@echo "  docker-install-symfony6  composer install in container"
	@echo "  docker-update-bundle-symfony6  Update bundle from path repo"
	@echo "  up-all       Install dependencies in all demos (no Docker)"
	@echo "  clean-all    Remove vendor/ and var/ in all demos"
	@echo "  release-verify  Start each demo, healthcheck (HTTP 200), then stop (for release-check)"
	@echo ""

up-all:
	@for d in $(DEMOS); do $(MAKE) -C .. demo-up-$$d; done

clean-all:
	@for d in $(DEMOS); do rm -rf $$d/vendor $$d/var; echo "Cleaned $$d"; done

release-verify:
	@for d in $(DEMOS); do \
		port=$$(grep '^PORT=' $$d/.env 2>/dev/null | cut -d= -f2 | tr -d '\r'); \
		[ -z "$$port" ] && port=$$(case $$d in symfony6) echo 8006;; symfony7) echo 8007;; symfony8) echo 8008;; *) echo 8000;; esac); \
		echo "🔍 Release verify: $$d (port $$port)..."; \
		$(MAKE) docker-up-$$d; \
		echo "⏳ Waiting for $$d to be ready..."; sleep 15; \
		code=$$(curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1:$$port 2>/dev/null || echo "000"); \
		$(MAKE) docker-down-$$d 2>/dev/null || true; \
		if [ "$$code" != "200" ]; then echo "❌ $$d healthcheck failed (HTTP $$code)"; exit 1; fi; \
		echo "✅ $$d OK (HTTP $$code)"; \
	done
	@echo "✅ All demos passed release-verify."

up-symfony6:
	$(MAKE) -C .. demo-up-symfony6

up-symfony7:
	$(MAKE) -C .. demo-up-symfony7

up-symfony8:
	$(MAKE) -C .. demo-up-symfony8

docker-up-symfony6:
	$(MAKE) -C symfony6 up

docker-up-symfony7:
	$(MAKE) -C symfony7 up

docker-up-symfony8:
	$(MAKE) -C symfony8 up

docker-down-symfony6:
	$(MAKE) -C symfony6 down

docker-down-symfony7:
	$(MAKE) -C symfony7 down

docker-down-symfony8:
	$(MAKE) -C symfony8 down

docker-restart-symfony6:
	$(MAKE) -C symfony6 restart

docker-restart-symfony7:
	$(MAKE) -C symfony7 restart

docker-restart-symfony8:
	$(MAKE) -C symfony8 restart

docker-build-symfony6:
	$(MAKE) -C symfony6 build

docker-build-symfony7:
	$(MAKE) -C symfony7 build

docker-build-symfony8:
	$(MAKE) -C symfony8 build

docker-install-symfony6:
	$(MAKE) -C symfony6 install

docker-install-symfony7:
	$(MAKE) -C symfony7 install

docker-install-symfony8:
	$(MAKE) -C symfony8 install

docker-update-bundle-symfony6:
	$(MAKE) -C symfony6 update-bundle

docker-update-bundle-symfony7:
	$(MAKE) -C symfony7 update-bundle

docker-update-bundle-symfony8:
	$(MAKE) -C symfony8 update-bundle
