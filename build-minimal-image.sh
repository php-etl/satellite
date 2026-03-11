#!/bin/bash
# Build l'image OCI minimale pour le compiler Satellite.
#
# Usage:
#   1. Avec binaire FrankenPHP (extraire d'abord depuis static-build):
#      ./build-minimal-image.sh /path/to/satellite-binary
#
#   2. Sans binaire (crée un stub pour tester le Dockerfile):
#      ./build-minimal-image.sh

set -e

BINARY="${1:-}"
BUILD_DIR="$(cd "$(dirname "$0")" && pwd)"

if [ -n "$BINARY" ]; then
    cp "$BINARY" "$BUILD_DIR/satellite"
else
    echo "Aucun binaire fourni. Création d'un stub pour validation du Dockerfile."
    echo '#!/bin/sh
echo "Satellite compiler (stub - build FrankenPHP pour le binaire réel)"
echo "Usage: satellite php-cli bin/satellite --help"
exit 0' > "$BUILD_DIR/satellite"
    chmod +x "$BUILD_DIR/satellite"
fi

docker build -t satellite:minimal -f "$BUILD_DIR/Dockerfile.minimal" "$BUILD_DIR"
echo "Image satellite:minimal créée."
docker run --rm satellite:minimal
