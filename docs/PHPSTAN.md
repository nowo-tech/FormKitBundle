## PHPStan

This bundle is kept at PHPStan level 8.

The baseline file is intentionally minimal:

```neon
parameters:
	ignoreErrors: []
```

Do not add ignored errors or use the baseline to silence issues. New PHPStan findings should be fixed in code or tests so that `docker compose exec -T php composer phpstan` continues to exit successfully with no suppressed errors.
