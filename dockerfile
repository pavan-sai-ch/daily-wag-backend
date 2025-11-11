FROM php:8.2-apache

# 1. Install the PHP extensions you need
RUN docker-php-ext-install pdo pdo_mysql mysqli

# 2. Enable the Apache "rewrite" module
# This is ESSENTIAL for your .htaccess router to work
RUN a2enmod rewrite

# 3. Set the web root to your /public folder
# This is a critical security step. It prevents anyone from
# accessing files in your /src, /api, or /vendor folders.
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf