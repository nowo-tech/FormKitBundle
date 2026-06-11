#!/bin/sh
set -e

if [ "${APP_ENV:-prod}" = "dev" ] && [ -f /etc/frankenphp/Caddyfile.dev ]; then
	cp /etc/frankenphp/Caddyfile.dev /etc/frankenphp/Caddyfile
fi

cd /app
mkdir -p var/cache var/log var
chmod -R 777 var 2>/dev/null || true

if [ ! -f vendor/autoload_runtime.php ]; then
    echo "Installing dependencies..."
    composer install --no-interaction
    echo "Composer install done."
fi

if [ -f bin/console ]; then
	# FOSCKEditorBundle: CKEditor first, then symlink bundles (nowoselectallchoice, nowoformkit, …).
	# CKEditor 4.23+ (LTS) exige licencia comercial; fijamos 4.22.1 (OSS).
	php bin/console ckeditor:install --no-interaction --no-progress-bar --clear=drop --tag=4.22.1 2>/dev/null || true
	php bin/console importmap:install --no-interaction 2>/dev/null || true
	php bin/console assets:install public --symlink --no-interaction 2>/dev/null || true
	php bin/console typescript:build --no-interaction 2>/dev/null || true
fi

if [ -f package.json ]; then
	echo "Building frontend assets (Vite + TypeScript)..."
	pnpm install
	pnpm run build
	echo "Vite build done."
fi

exec frankenphp run --config /etc/frankenphp/Caddyfile --adapter caddyfile
