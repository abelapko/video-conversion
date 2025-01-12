# Используем официальный образ PHP
FROM php:8.4-cli

# install Git
RUN apt-get update && apt-get install -y git

# enable php extension sockets
RUN docker-php-ext-install sockets

# Устанавливаем Composer (используя официальный скрипт)
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Установить зависимости и Xdebug
RUN apt-get update && apt-get install -y \
    libzip-dev \
    unzip \
    && docker-php-ext-install zip \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug

# Configure Xdebug
COPY xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

# Устанавливаем рабочую директорию для монтирования
WORKDIR /app
