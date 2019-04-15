FROM php:5-apache

RUN a2enmod rewrite
RUN apt-get update && \
    apt-get install -y libxml2 libxml2-dev && \
    docker-php-ext-install mysqli soap dom
RUN yes | pecl install xdebug-2.5.5 \
    && echo "zend_extension=$(find /usr/local/lib/php/extensions/ -name xdebug.so)" > /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.remote_connect_back=0" >> /usr/local/etc/php/conf.d/xdebug.ini
