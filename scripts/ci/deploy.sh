#!/usr/bin/env bash
# Build (from local source) and restart the Moodle stack.
# No registry round-trip: docker compose build → up.
#
# Usage:
#   ./scripts/ci/deploy.sh            # build + (re)start everything
#   ./scripts/ci/deploy.sh --rebuild  # force rebuild with --no-cache
#   ./scripts/ci/deploy.sh --app-only # rebuild + restart only the moodle container
set -euo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
COMPOSE="docker compose --file $REPO_ROOT/docker-compose.yml"

REBUILD=false
APP_ONLY=false
BUILD_ARGS=()

while [[ $# -gt 0 ]]; do
    case $1 in
        --rebuild)   REBUILD=true;  BUILD_ARGS+=(--no-cache); shift ;;
        --app-only)  APP_ONLY=true; shift ;;
        *)           echo "Unknown option: $1"; exit 1 ;;
    esac
done

# Ensure .env exists
if [[ ! -f "$REPO_ROOT/.env" ]]; then
    echo "==> No .env found — copying from .env.example"
    cp "$REPO_ROOT/.env.example" "$REPO_ROOT/.env"
    echo "    Edit $REPO_ROOT/.env before continuing (set MOODLE_DB_PASS etc.)"
fi

echo "==> Building Moodle image from local source"
if [[ "$APP_ONLY" == true ]]; then
    $COMPOSE build "${BUILD_ARGS[@]}" moodle
else
    $COMPOSE build "${BUILD_ARGS[@]}"
fi

echo ""
echo "==> Starting stack"
if [[ "$APP_ONLY" == true ]]; then
    $COMPOSE up -d --no-deps moodle
else
    $COMPOSE up -d
fi

echo ""
echo "==> Waiting for Moodle to become healthy..."
timeout 120 bash -c "
    until docker inspect --format='{{.State.Health.Status}}' \
        \$(docker compose --file $REPO_ROOT/docker-compose.yml ps -q moodle) \
        2>/dev/null | grep -q 'healthy'; do
        printf '.'; sleep 3
    done
" && echo " ready" || echo " timed out (check 'docker compose logs moodle')"

echo ""
WWWROOT=$(grep MOODLE_WWWROOT "$REPO_ROOT/.env" 2>/dev/null | cut -d= -f2 || echo "http://localhost")
echo "==> Moodle is up at $WWWROOT"
echo "    Logs: docker compose logs -f moodle"
