#!/bin/sh
set -e

# Fix ownership of Laravel writable directories at every container start.
# The volume mount at runtime overlays the image layer's chown, so these
# dirs can end up root-owned (fresh clone, previous root session, etc.).
sudo chown -R "$(id -u):$(id -g)" \
    /var/www/storage \
    /var/www/bootstrap/cache \
    2>/dev/null || true

exec "$@"
