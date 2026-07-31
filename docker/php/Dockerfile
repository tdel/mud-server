# syntax=docker/dockerfile:1
FROM php:8.5-cli
ARG TIMEZONE=Europe/Paris
ENV PHP_MEMORY_LIMIT=128M

ADD https://github.com/mlocati/docker-php-extension-installer/releases/download/2.11.12/install-php-extensions /usr/local/bin/

RUN chmod uga+x /usr/local/bin/install-php-extensions && sync

# Set timezone
RUN ln -snf /usr/share/zoneinfo/${TIMEZONE} /etc/localtime && echo ${TIMEZONE} > /etc/timezone \
 && printf '[PHP]\ndate.timezone = "%s"\n', ${TIMEZONE} > /usr/local/etc/php/conf.d/tzone.ini \
 && "date"

# io_uring is opt-in in swoole >=6 and, under this environment's default
# seccomp profile, io_uring_setup() is blocked outright — skip it and use
# swoole's epoll-based reactor instead (see the Swoole migration plan for
# the segfault-on-shutdown this avoided).
ENV IPE_SWOOLE_WITHOUT_IOURING=1

RUN --mount=type=cache,target=/var/cache/apt,sharing=locked \
    --mount=type=cache,target=/var/lib/apt,sharing=locked \
    install-php-extensions \
    opcache \
    apcu \
    bcmath \
    gd \
    intl \
    memcached\
    pcntl \
    pdo \
    pdo_pgsql \
    zip \
    swoole \
    xdebug

WORKDIR "/var/www"

CMD ["sleep", "infinity"]