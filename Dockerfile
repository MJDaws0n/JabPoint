# Use the official PHP image with Apache
FROM php:8.2-apache

# Accept APP_ENV argument
ARG APP_ENV=development

# Install required libraries, enable GD with WebP support, and install Composer
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libwebp-dev \
    unzip \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install gd \
    && docker-php-ext-enable gd \
    && a2enmod rewrite \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Set working directory for the web server
WORKDIR /var/www/html

# Copy project files
COPY . /var/www/html

# Ensure proper permissions for Apache
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Run composer install if composer.json is present
RUN if [ -f "composer.json" ]; then composer install --no-dev --optimize-autoloader; fi

# Set Apache DocumentRoot to /var/www/html/src
RUN sed -i 's|/var/www/html|/var/www/html/src|' /etc/apache2/sites-available/000-default.conf

# Enable .htaccess override for Apache
RUN echo "<Directory /var/www/html/src>" >> /etc/apache2/apache2.conf \
    && echo "    AllowOverride All" >> /etc/apache2/apache2.conf \
    && echo "</Directory>" >> /etc/apache2/apache2.conf

# Set environment-specific PHP settings
RUN if [ "$APP_ENV" = "production" ]; \
    then \
        echo "display_errors = Off" >> /usr/local/etc/php/conf.d/docker-php.ini; \
        echo "log_errors = On" >> /usr/local/etc/php/conf.d/docker-php.ini; \
        echo "error_log = /var/log/apache2/php_error.log" >> /usr/local/etc/php/conf.d/docker-php.ini; \
    else \
        echo "display_errors = On" >> /usr/local/etc/php/conf.d/docker-php.ini; \
        echo "log_errors = Off" >> /usr/local/etc/php/conf.d/docker-php.ini; \
    fi

# Expose port
EXPOSE 80