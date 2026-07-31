FROM php:8.4-fpm-alpine

RUN apk add --no-cache \
    postgresql-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    oniguruma-dev \
    icu-dev \
    autoconf \
    g++ \
    make \
    git \
    curl \
    unzip

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo \
        pdo_pgsql \
        pgsql \
        zip \
        gd \
        mbstring \
        exif \
        pcntl \
        bcmath \
        intl

RUN pecl install redis && docker-php-ext-enable redis

# Code coverage driver for PHPUnit. Loaded but inert (pcov.enabled=0 in
# local.ini); turn it on per-run with `php -d pcov.enabled=1 vendor/bin/phpunit`.
RUN pecl install pcov && docker-php-ext-enable pcov

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ARG UID=1000
ARG GID=1000
RUN addgroup -g ${GID} appgroup && \
    adduser -u ${UID} -G appgroup -s /bin/sh -D appuser

# Allow appuser to chown Laravel writable dirs at container startup only.
RUN apk add --no-cache sudo && \
    echo "appuser ALL=(root) NOPASSWD: /bin/chown" > /etc/sudoers.d/appuser && \
    chmod 0440 /etc/sudoers.d/appuser

WORKDIR /var/www

COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction 2>/dev/null || true

RUN chown -R appuser:appgroup /var/www/storage /var/www/bootstrap/cache 2>/dev/null || true

COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

USER appuser

EXPOSE 9000
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
