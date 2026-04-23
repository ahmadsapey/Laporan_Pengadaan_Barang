FROM richarvey/nginx-php-fpm:latest

# Copy semua file ke direktori web server
COPY . /var/www/html/

# Set permission agar bisa tulis file JSON
RUN chown -R nginx:nginx /var/www/html/ && \
    chmod -R 755 /var/www/html/

# Expose port 80
EXPOSE 80