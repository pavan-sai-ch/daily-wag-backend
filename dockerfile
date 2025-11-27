FROM php:8.2-apache

# 1. Install system dependencies
# git, zip, and unzip are required for Composer to work correctly
RUN apt-get update && apt-get install -y \
    git \
    zip \
    unzip

# 2. Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql mysqli

# 3. Enable the Apache "rewrite" module
# This is ESSENTIAL for your .htaccess router to work
RUN a2enmod rewrite

# 4. Install Composer
# This copies the composer binary from the official image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 5. Set the working directory
WORKDIR /var/www/html

# --- HERE IS THE MISSING PART ---

# 6. Copy Composer files first
# We copy just these files first so Docker can cache the installed dependencies.
# If you don't change composer.json, Docker won't re-run the slow install step.
COPY composer.json ./

# 7. Install Dependencies
# This reads composer.json and downloads the AWS SDK into /var/www/html/vendor
# --no-scripts and --no-autoloader make it faster and safer for building
RUN composer install --no-scripts --no-autoloader

# 8. Generate Autoload files
# This creates the final map so PHP can find the AWS classes
RUN composer dump-autoload --optimize

# -------------------------------

# 9. Set the web root to your /public folder
# This is a critical security step. It prevents anyone from
# accessing files in your /src, /api, or /vendor folders directly.
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf