FROM yiisoftware/yii2-php:8.4-fpm-nginx

# Install PCOV for code coverage
RUN pecl install pcov && docker-php-ext-enable pcov
