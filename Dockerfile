# Lightweight: official PHP-CLI on Alpine, no framework, no fpm.
FROM php:8.3-cli-alpine

# GD (+FreeType) for on-the-fly Open Graph image generation. Build deps are
# added, used, then removed to keep the final image small. Fonts are bundled
# in assets/fonts, so no font package is needed.
RUN apk add --no-cache freetype libjpeg-turbo libpng \
 && apk add --no-cache --virtual .build-deps freetype-dev libjpeg-turbo-dev libpng-dev \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j"$(nproc)" gd \
 && apk del .build-deps

WORKDIR /app
COPY . /app

# Run multiple worker processes so a slow remote metadata fetch never
# blocks static asset requests.
ENV PHP_CLI_SERVER_WORKERS=8

EXPOSE 8080

HEALTHCHECK --interval=30s --timeout=5s --start-period=5s --retries=3 \
  CMD wget -qO- http://127.0.0.1:8080/robots.txt >/dev/null 2>&1 || exit 1

CMD ["php", "-S", "0.0.0.0:8080", "router.php"]
