FROM php:8.2-apache

# MySQLi start extension
RUN docker-php-ext-install mysqli pdo pdo_mysql

# copy all code files to the server root
COPY . /var/www/html/

# uploads give permissions to the uploads folder
RUN mkdir -p /var/www/html/uploads && chmod -R 777 /var/www/html/uploads

EXPOSE 80
