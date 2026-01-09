FROM yiisoftware/yii2-php:8.4-fpm

# PCOV для покрытия кода тестами
RUN pecl install pcov && docker-php-ext-enable pcov

# Redis для кэширования
RUN pecl install redis && docker-php-ext-enable redis

# Sockets для Buggregator Trap
RUN docker-php-ext-install sockets

# Node.js для Prettier и других инструментов
RUN apt-get update && apt-get install -y nodejs npm && rm -rf /var/lib/apt/lists/*

# Фикс ошибки git ownership в Docker
RUN git config --global --add safe.directory /app

# Создаем пользователя с ID, переданным через аргументы сборки
ARG UID=1000
ARG GID=1000
RUN groupadd -g $GID appuser && useradd -u $UID -g appuser -m -s /bin/bash appuser
