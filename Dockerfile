FROM php:8.2-apache

# Sakinisha cURL na SSL support
RUN apt-get update && apt-get install -y \
    libcurl4-openssl-dev \
    pkg-config \
    libssl-dev \
    && docker-php-ext-install curl

RUN a2enmod rewrite
COPY . /var/www/html/
EXPOSE 80
