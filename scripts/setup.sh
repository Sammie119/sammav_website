#!/usr/bin/env bash
# Installs WordPress in the Docker stack, activates the Sammav theme and plugin,
# and seeds pages, menu and placeholder content. Safe to run more than once.
#
# Usage: ./scripts/setup.sh
set -euo pipefail

cd "$(dirname "$0")/.."

[ -f .env ] || cp .env.example .env
set -a
# shellcheck disable=SC1091
. ./.env
set +a

wp() { docker compose run --rm -T wpcli "$@"; }

echo "→ Starting containers"
docker compose up -d

echo "→ Waiting for WordPress files"
for _ in $(seq 1 60); do
	if docker compose exec -T wordpress test -f /var/www/html/wp-config.php; then break; fi
	sleep 2
done

if ! wp core is-installed >/dev/null 2>&1; then
	echo "→ Installing WordPress"
	wp core install \
		--url="$SITE_URL" \
		--title="$SITE_TITLE" \
		--admin_user="$ADMIN_USER" \
		--admin_password="$ADMIN_PASSWORD" \
		--admin_email="$ADMIN_EMAIL" \
		--skip-email
else
	echo "→ WordPress already installed"
fi

echo "→ Configuring"
wp option update blogdescription "Reliable IT services in Ghana: networking, software, web, cloud and security"
wp option update timezone_string "Africa/Accra"
wp option update date_format "j F Y"
wp option update default_comment_status closed
wp option update default_ping_status closed
wp rewrite structure '/%postname%/' --hard

echo "→ Activating theme and plugin"
wp theme activate sammav
wp plugin activate sammav-core
# Remove bundled extras that the site does not use.
wp plugin delete akismet hello >/dev/null 2>&1 || true

if ! wp user get "$EDITOR_USER" >/dev/null 2>&1; then
	echo "→ Creating Editor user"
	wp user create "$EDITOR_USER" "$EDITOR_EMAIL" --role=editor --user_pass="$EDITOR_PASSWORD"
fi

echo "→ Seeding content"
wp eval-file /seed/seed.php
wp rewrite flush --hard

echo
echo "Done."
echo "  Site:       $SITE_URL"
echo "  Admin:      $SITE_URL/wp-admin  ($ADMIN_USER / see .env)"
echo "  Editor:     $EDITOR_USER / see .env"
echo "  Mailpit:    http://localhost:${MAIL_UI_PORT:-8025}"
