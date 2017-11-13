FROM php:5-apache

RUN a2enmod rewrite
RUN apt-get update && \
    apt-get install -y libxml2-dev && \
    docker-php-ext-install mysqli soap

