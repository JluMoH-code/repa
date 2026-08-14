FROM php:8.3-fpm

# Системные зависимости, нужные для сборки PHP-расширений
RUN apt-get update && apt-get install -y \
    git \
    curl \
    unzip \
    zip \
    libpq-dev \
    libzip-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libonig-dev \
    libicu-dev \
    && rm -rf /var/lib/apt/lists/*

# GD с поддержкой jpeg/freetype
RUN docker-php-ext-configure gd --with-jpeg --with-freetype

# PHP-расширения, нужные проекту
RUN docker-php-ext-install \
    pdo_pgsql \
    pgsql \
    gd \
    zip \
    bcmath \
    mbstring \
    exif \
    pcntl \
    opcache \
    intl

# Redis-расширение ставится через PECL
RUN pecl install redis && docker-php-ext-enable redis

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Свой php.ini для dev-окружения (лежит рядом, в docker/app/local.ini)
COPY docker/app/local.ini /usr/local/etc/php/conf.d/local.ini

# Entrypoint приводит владельца/права storage и bootstrap/cache к www-data при
# каждом старте контейнера, потому что эти папки переопределяются bind-mount'ом
# с хоста в рантайме — фиксировать права здесь, в Dockerfile, было бы бесполезно.
COPY docker/app/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]
