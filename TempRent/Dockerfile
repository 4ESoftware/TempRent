FROM php:7.4-apache

# 1. prerequisites
ENV COMPOSER_MEMORY_LIMIT=-1

RUN apt-get update && apt-get install -y \
    wget \
    libpq-dev \
    curl \
    zlib1g-dev \
    git \
    zip \
    unzip \
    sudo \
    libmemcached-dev \
    libicu-dev \
    libbz2-dev \
    libpng-dev \
    libjpeg-dev \
    libzip-dev \
    libonig-dev \
    libmcrypt-dev \
    libreadline-dev \
    libfreetype6-dev \
    g++

# 2. apache configs + document root
ENV APACHE_DOCUMENT_ROOT=/var/www/html/web
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# 3. mod_rewrite for URL rewrite and mod_headers for .htaccess extra headers like Access-Control-Allow-Origin-
RUN a2enmod rewrite headers

RUN docker-php-ext-install bz2
RUN docker-php-ext-install intl
RUN docker-php-ext-install iconv
RUN docker-php-ext-install bcmath
RUN docker-php-ext-install opcache
RUN docker-php-ext-install calendar
RUN docker-php-ext-install pdo_mysql
RUN docker-php-ext-install zip
RUN docker-php-ext-install json

# 4. Install Memcached for php 7

RUN mkdir -p /usr/src/php/ext/memcached
WORKDIR /usr/src/php/ext/memcached
RUN wget https://github.com/php-memcached-dev/php-memcached/archive/v3.1.3.zip; unzip /usr/src/php/ext/memcached/v3.1.3.zip
RUN mv /usr/src/php/ext/memcached/php-memcached-3.1.3/* /usr/src/php/ext/memcached/

RUN docker-php-ext-configure memcached && docker-php-ext-install memcached

# 5. composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY ./memcache.ini $PHP_INI_DIR/conf.d/
COPY ./backend.conf /etc/apache2/sites-available/backend.conf
COPY ./service /var/www/html

RUN chown www-data:www-data /var/www/html/
RUN sudo a2dissite 000-default.conf
RUN sudo a2ensite backend.conf
RUN sudo service apache2 start

WORKDIR /var/www/html

RUN sudo chown -R www-data:www-data ./

EXPOSE 80
EXPOSE 8000
