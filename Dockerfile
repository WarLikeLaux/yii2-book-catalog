FROM yiisoftware/yii2-php:8.4-fpm

# PCOV для покрытия кода тестами
RUN pecl install pcov && docker-php-ext-enable pcov

# Redis для кэширования
RUN pecl install redis && docker-php-ext-enable redis

# Sockets для Buggregator Trap
RUN docker-php-ext-install sockets

# Фикс ошибки git ownership в Docker
RUN git config --global --add safe.directory /app
