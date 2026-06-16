#!/usr/bin/env bash
# Build the Moodle Docker image from local source.
# Usage: ./scripts/ci/build.sh [--no-cache] [--tag custom-tag]
set -euo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
IMAGE_TAG="moodle-local:latest"
BUILD_ARGS=()

while [[ $# -gt 0 ]]; do
    case $1 in
        --no-cache)  BUILD_ARGS+=(--no-cache); shift ;;
        --tag)       IMAGE_TAG="$2"; shift 2 ;;
        *)           echo "Unknown option: $1"; exit 1 ;;
    esac
done

echo "==> Building image: $IMAGE_TAG"
echo "    Source: $REPO_ROOT"
echo "    $(date)"

docker build \
    "${BUILD_ARGS[@]}" \
    --tag "$IMAGE_TAG" \
    --file "$REPO_ROOT/Dockerfile" \
    "$REPO_ROOT"

echo ""
echo "==> Build complete: $IMAGE_TAG"
echo "    Run './scripts/ci/deploy.sh' to apply it."
