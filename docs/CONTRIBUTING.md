# Contributing

Development uses Docker and Make. From the bundle root:

- `make up` (or ensure container is running), then `make install`
- `make test` — run tests
- `make test-coverage` — tests with coverage
- `make cs-check` / `make cs-fix` — code style (PHP-CS-Fixer)
- `make qa` — cs-check + test

See the root [Makefile](../Makefile) and [README](../README.md).

## Code of Conduct

This project follows the [Contributor Covenant Code of Conduct](../CODE_OF_CONDUCT.md). By participating, you are expected to uphold it. Please report unacceptable behavior to **hectorfranco@nowo.tech**.

## Language policy

- **Documentation** (Markdown in the repo, `README`, `docs/`, issue templates, inline comments in config examples): **English only**.
- **PHP**: PHPDoc, inline comments, `composer.json` descriptions, and exception messages intended for developers: **English only**.
- **Twig** (bundle views under `src/Resources/views/`, including commented examples in `{# #}` blocks): **English only** for developer-facing sample text.
- **TypeScript / JavaScript**: JSDoc where used, and inline comments: **English only**.
- **End-user translations** (e.g. `translations/messages.es.yaml` in demos) may be localized; that is **not** developer documentation.

Pull requests that add or change non-English developer-facing text may be rejected or asked to be translated.

## Git hooks (REQ-GIT-001)

Do **not** add `Co-authored-by: Cursor` or `cursoragent@cursor.com` trailers to commit messages.

```bash
make setup-hooks
make check-no-cursor-coauthor
```

`make setup-hooks` installs `.githooks/commit-msg` (or sets `core.hooksPath` to `.githooks`). Run it once per clone before your first commit.
If CI fails because trailers are already on the remote, see [GITHUB_CI.md](GITHUB_CI.md) (REQ-GIT-001) and run `make strip-cursor-coauthor-from-history` before `git push --force-with-lease`.
