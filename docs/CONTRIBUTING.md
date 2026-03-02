# Contributing

Development uses Docker and Make. From the bundle root:

- `make up` (or ensure container is running), then `make install`
- `make test` — run tests
- `make test-coverage` — tests with coverage
- `make cs-check` / `make cs-fix` — code style (PHP-CS-Fixer)
- `make qa` — cs-check + test

See the root [Makefile](../Makefile) and [README](../README.md). All documentation and PHPDoc must be in English.
