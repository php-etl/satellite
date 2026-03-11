# Build FrankenPHP - Compiler Satellite en binaire standalone

Spike de validation : packaging du compiler Satellite en binaire unique via FrankenPHP.

## Prérequis

- Docker (build multi-plateforme linux/amd64)
- Contexte de build : **racine du monorepo** (parent de `compiler/`)

## Build

Depuis la racine du dépôt :

```bash
docker build -t satellite-frankenphp -f compiler/static-build.Dockerfile .
```

## Extraction du binaire

```bash
docker cp $(docker create --name satellite-tmp satellite-frankenphp):/go/src/app/dist/frankenphp-linux-x86_64 satellite
docker rm satellite-tmp
chmod +x satellite
```

## Exécution

Le compiler est une CLI Symfony. Utiliser `php-cli` :

```bash
./satellite php-cli bin/satellite --help
./satellite php-cli bin/satellite build --output-dir /tmp/out .
```

## Mesure de la taille

```bash
ls -lh satellite
```

## Résultats du spike (2025-03)

- **Stage 1 (app-builder)** : OK — Composer install avec `--ignore-platform-reqs`, résolution des symlinks path.
- **Stage 2 (FrankenPHP)** : Blocage — Le script `build-static.sh` échoue sur `./spc` (static-php-cli) : téléchargement ou chemin incorrect dans l'image `dunglas/frankenphp:static-builder-gnu`.
- **Contexte de build** : Réduit à ~662 KB grâce au `.dockerignore` (exclusion de vendor, node_modules, .git).

## Image OCI minimale

Une fois le binaire extrait, créer l'image minimale :

```bash
./compiler/build-minimal-image.sh ./satellite
# ou pour tester le Dockerfile sans binaire réel:
./compiler/build-minimal-image.sh
```

Voir aussi [Dockerfile.minimal](compiler/Dockerfile.minimal).

## Références

- [FrankenPHP - PHP Apps As Standalone Binaries](https://frankenphp.dev/docs/embed/)
- [Kévin Dunglas - PHP and Symfony Apps As Standalone Binaries](https://dunglas.dev/2023/12/php-and-symfony-apps-as-standalone-binaries/)
