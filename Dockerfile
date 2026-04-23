FROM php:8.1-apache

# Disable conflicting MPM modules
RUN a2dismod mpm_event mpm_worker && a2enmod mpm_prefork

# Copy semua file ke direktori web server
COPY . /var/www/html/

# Set permission agar bisa tulis file JSON
RUN chown -R www-data:www-data /var/www/html/ && \
    chmod -R 755 /var/www/html/

# Expose port 80
EXPOSE 80