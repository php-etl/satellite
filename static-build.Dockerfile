# Spike FrankenPHP: build binaire standalone du compiler Satellite
# Référence: https://frankenphp.dev/docs/embed

# Stage 1: Préparation de l'app (compiler + vendor avec path deps résolues)
FROM composer:2 AS app-builder

WORKDIR /build

# Copier le monorepo pour résoudre les path repositories
COPY . .

# Installer les dépendances du compiler (symlinks par défaut)
# --no-plugins pour éviter les plugins Composer qui pourraient échouer
RUN cd compiler && composer install \
    --no-ansi \
    --no-dev \
    --no-interaction \
    --no-plugins \
    --no-progress \
    --no-scripts \
    --optimize-autoloader \
    --ignore-platform-reqs

# Résoudre les symlinks path: copier en fichiers réels pour l'embed
RUN cd compiler && \
    mkdir -p vendor-resolved && \
    cp -rL vendor/* vendor-resolved/ 2>/dev/null || cp -r vendor/* vendor-resolved/ && \
    rm -rf vendor && mv vendor-resolved vendor

# Nettoyer les dev dependencies résiduelles
RUN cd compiler && rm -rf tests/ \
    vendor/phpstan vendor/phpunit vendor/rector vendor/fakerphp \
    vendor/friendsofphp vendor/justinrainbow vendor/infection vendor/mikey179 \
    2>/dev/null || true

# Stage 2: Build du binaire FrankenPHP
FROM --platform=linux/amd64 dunglas/frankenphp:static-builder-gnu

WORKDIR /go/src/app/dist/app

# Copier l'app préparée
COPY --from=app-builder /build/compiler .

WORKDIR /go/src/app/

# Build du binaire statique
RUN EMBED=dist/app/ ./build-static.sh
