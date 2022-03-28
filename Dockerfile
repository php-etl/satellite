FROM composer AS builder

WORKDIR /app

COPY composer.json composer.json
COPY composer.lock composer.lock

RUN composer install --no-ansi --no-dev --no-interaction --no-plugins --no-progress --no-scripts --optimize-autoloader

FROM php:8.1-cli-alpine

WORKDIR /app

COPY src/ src/
COPY --from=builder /app/vendor/ vendor/
COPY bin/satellite bin/satellite

CMD [ '/app/bin/satellite' ]
