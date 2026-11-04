#!/usr/bin/env bash
# Point the local app at the local Postgres database or the live Neon database.
#
#   scripts/db.sh local    use local Postgres (postgresql://<you>@127.0.0.1:5432/parameter)
#   scripts/db.sh live     use the live Neon database from DATABASE_URL (same data as production!)
#   scripts/db.sh status   show which database is active
#
# It only adds/removes a DB_URL line in .env; the `pgsql` connection prefers DB_URL over DATABASE_URL.
set -euo pipefail
cd "$(dirname "$0")/.."

LOCAL_URL="${LOCAL_DB_URL:-postgresql://$(whoami)@127.0.0.1:5432/parameter}"

status() {
    if grep -q '^DB_URL=' .env; then
        echo "Database: LOCAL  ($(grep '^DB_URL=' .env | cut -d= -f2-))"
    else
        echo "Database: LIVE   (Neon, $(grep '^DATABASE_URL=' .env | grep -oE '@[^/:]+' | tr -d @))"
    fi
}

case "${1:-status}" in
    local)
        sed -i.bak '/^DB_URL=/d' .env && rm -f .env.bak
        printf 'DB_URL=%s\n' "$LOCAL_URL" >> .env
        php artisan config:clear >/dev/null
        status
        if ! pg_isready -q -h 127.0.0.1 -p 5432; then
            echo "Local Postgres isn't running. Start it with: brew services start postgresql@17"
        fi
        echo "First time? Run: php artisan migrate --seed"
        ;;
    live)
        sed -i.bak '/^DB_URL=/d' .env && rm -f .env.bak
        php artisan config:clear >/dev/null
        status
        echo "Careful: changes now affect production data."
        ;;
    status)
        status
        ;;
    *)
        echo "Usage: scripts/db.sh [local|live|status]" >&2
        exit 1
        ;;
esac
echo "Restart \`php artisan serve\` (or composer dev) so it picks up the change."
