#!/usr/bin/env bash
#
# Deploy isitvibecoded to the home server.
#
# Syncs this project to the server over SSH (using your ~/.ssh/config host),
# then (re)builds and starts the Docker container via docker compose.
#
# Usage:
#   ./deploy.sh              # sync + build + up -d
#   ./deploy.sh --no-build   # sync + up -d (skip rebuild)
#   ./deploy.sh --logs       # sync + build + up -d, then tail logs
#
set -euo pipefail

# --- Config -----------------------------------------------------------------
SSH_HOST="${SSH_HOST:-homeserver}"
REMOTE_DIR="${REMOTE_DIR:-/data/apps/iazz.fr/isitvibecoded}"
# ----------------------------------------------------------------------------

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

BUILD=1
TAIL_LOGS=0
for arg in "$@"; do
  case "$arg" in
    --no-build) BUILD=0 ;;
    --logs)     TAIL_LOGS=1 ;;
    *) echo "Unknown option: $arg" >&2; exit 1 ;;
  esac
done

echo "==> Syncing project to ${SSH_HOST}:${REMOTE_DIR}"
ssh "$SSH_HOST" "mkdir -p '$REMOTE_DIR'"

rsync -az --delete \
  --exclude='.git' \
  --exclude='.gitignore' \
  --exclude='*.md' \
  --exclude='assets/og-src.svg' \
  --exclude='node_modules' \
  --exclude='.DS_Store' \
  --exclude='scratchpad' \
  --exclude='deploy.sh' \
  ./ "${SSH_HOST}:${REMOTE_DIR}/"

echo "==> Starting Docker on ${SSH_HOST}"
if [ "$BUILD" -eq 1 ]; then
  ssh "$SSH_HOST" "cd '$REMOTE_DIR' && docker compose up -d --build"
else
  ssh "$SSH_HOST" "cd '$REMOTE_DIR' && docker compose up -d"
fi

echo "==> Container status"
ssh "$SSH_HOST" "cd '$REMOTE_DIR' && docker compose ps"

if [ "$TAIL_LOGS" -eq 1 ]; then
  echo "==> Tailing logs (Ctrl-C to stop)"
  ssh "$SSH_HOST" "cd '$REMOTE_DIR' && docker compose logs -f --tail=50"
fi

echo "==> Done."
