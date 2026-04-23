FROM php:8.1-apache

# Force disable all conflicting MPM modules and enable only prefork
RUN rm -f /etc/apache2/mods-enabled/mpm_event.load /etc/apache2/mods-enabled/mpm_worker.load /etc/apache2/mods-enabled/mpm_itk.load && \
    a2enmod mpm_prefork

# Copy semua file ke direktori web server
COPY . /var/www/html/

# Set permission agar bisa tulis file JSON
RUN chown -R www-data:www-data /var/www/html/ && \
    chmod -R 755 /var/www/html/

# Expose port 80
EXPOSE 80