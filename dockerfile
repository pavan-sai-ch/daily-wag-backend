FROM php:8.2-apache

RUN docker-php-ext-install pdo pdo_mysql

WORKDIR /var/www/html
COPY ./public/ /var/www/html/
COPY ./api /var/www/api
COPY ./src /var/www/src

EXPOSE 80
