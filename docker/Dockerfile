FROM php:8.1-apache

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions gd xdebug mongodb

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

COPY . /var/www/

COPY ./docker/apache2.conf /etc/apache2/sites-available/000-default.conf

RUN cd /var/www && \
    composer install

RUN chmod -R 777 /var/www/var

WORKDIR /var/www/

EXPOSE 80