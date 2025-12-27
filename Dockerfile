FROM yiisoftware/yii2-php:8.4-fpm-nginx

# PCOV для покрытия кода тестами
RUN pecl install pcov && docker-php-ext-enable pcov

# Фикс ошибки git ownership в Docker
RUN git config --global --add safe.directory /app
