FROM caddy:2-builder-alpine AS builder

RUN xcaddy build \
    --with github.com/caddyserver/nginx-adapter \
    --with github.com/hairyhenderson/caddy-teapot-module@v0.0.3-0

FROM caddy:2-alpine

COPY --from=builder /usr/bin/caddy /usr/bin/caddy
