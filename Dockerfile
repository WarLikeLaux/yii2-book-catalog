FROM dunglas/frankenphp:1-php8.4

# Расширения: совпадают с FPM (где возможно) + Franken-специфичные
RUN install-php-extensions \
    pdo_mysql \
    pdo_pgsql \
    intl \
    gd \
    zip \
    opcache \
    pcntl \
    apcu \
    redis \
    sockets \
    pcov

# Системные утилиты для Composer и Git
RUN apt-get update && apt-get install -y git unzip

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Фикс ошибки git ownership (как в FPM)
RUN git config --global --add safe.directory /app

ARG UID=1000
ARG GID=1000
RUN groupadd -g $GID appuser 2>/dev/null || true && \
    useradd -u $UID -g $GID -m -s /bin/bash appuser 2>/dev/null || true

ENV PHP_INI_SCAN_DIR="/usr/local/etc/php/conf.d"

COPY docker/franken/php.ini /usr/local/etc/php/conf.d/99-app.ini

WORKDIR /app

RUN mkdir -p /app/runtime /app/web/assets && \
    chmod -R 777 /app/runtime /app/web/assets

EXPOSE 80 443 443/udp
