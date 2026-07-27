FROM php:8.5-fpm
ARG TIMEZONE=Europe/Paris
ENV PHP_MEMORY_LIMIT=128M

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN chmod uga+x /usr/local/bin/install-php-extensions && sync

## Install GIT
RUN apt-get update && dpkg --configure -a
RUN apt-get autoremove  && apt-get autoclean
RUN apt-cache search git
#RUN apt-get install -y git iputils-ping

# Set timezone
RUN ln -snf /usr/share/zoneinfo/${TIMEZONE} /etc/localtime && echo ${TIMEZONE} > /etc/timezone \
 && printf '[PHP]\ndate.timezone = "%s"\n', ${TIMEZONE} > /usr/local/etc/php/conf.d/tzone.ini \
 && "date"

RUN install-php-extensions \
    opcache \
    apcu \
    bcmath \
    gd \
    intl \
    memcached\
    pdo \
    pdo_pgsql \
    zip \
    xdebug

WORKDIR "/var/www"