FROM php:8.2-apache

# 1. Install system dependencies
# git, zip, unzip: for Composer
# openssl: for generating SSL certificates
RUN apt-get update && apt-get install -y \
    git \
    zip \
    unzip \
    openssl

# 2. Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql mysqli

# 3. Enable Apache modules
# 'rewrite' for routing, 'ssl' for HTTPS, 'headers' for CORS/security
RUN a2enmod rewrite
RUN a2enmod ssl
RUN a2enmod headers

# 4. Generate Self-Signed SSL Certificate
# This creates a certificate valid for 365 days in the standard location
RUN openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
    -keyout /etc/ssl/private/apache-selfsigned.key \
    -out /etc/ssl/certs/apache-selfsigned.crt \
    -subj "/C=US/ST=State/L=City/O=Organization/CN=localhost"

# 5. Configure Apache to use the Certificate
# We modify the default-ssl.conf to point to our new key and cert
RUN sed -i 's!/etc/ssl/certs/ssl-cert-snakeoil.pem!/etc/ssl/certs/apache-selfsigned.crt!g' /etc/apache2/sites-available/default-ssl.conf
RUN sed -i 's!/etc/ssl/private/ssl-cert-snakeoil.key!/etc/ssl/private/apache-selfsigned.key!g' /etc/apache2/sites-available/default-ssl.conf

# 6. Enable the SSL Site
# This tells Apache to actually listen on port 443 using the config above
RUN a2ensite default-ssl

# 7. Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 8. Set working directory
WORKDIR /var/www/html

# 9. Copy Composer files first (Optimization)
COPY composer.json ./

# 10. Install Dependencies
RUN composer install --no-scripts --no-autoloader

# 11. Generate Autoload files
RUN composer dump-autoload --optimize

# 12. Set web root to /public for security
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf