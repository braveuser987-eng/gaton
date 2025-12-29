FROM php:8.2-apache
# Copia tu código al servidor
COPY . /var/www/html/
# Expone el puerto 80
EXPOSE 80
