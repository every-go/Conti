FROM php:8.2-apache

# Estensioni necessarie per parlare con MySQL tramite PDO
RUN docker-php-ext-install pdo pdo_mysql \
    && a2enmod rewrite

WORKDIR /var/www/html
