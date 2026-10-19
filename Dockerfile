FROM php:8.4.26-apache-bookworm

LABEL maintainer="sezzle"

ENV INSTALL_DIR=/var/www/html \
    APACHE_DOCUMENT_ROOT=/var/www/html/pub \
    COMPOSER_HOME=/var/www/.composer

# Magento code is not baked in; `process install` downloads it into $INSTALL_DIR on first run
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git unzip libfreetype6-dev libjpeg62-turbo-dev libpng-dev libicu-dev libxslt1-dev libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" bcmath ftp gd intl opcache pdo_mysql soap sockets xsl zip \
    && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite \
    && sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && printf 'memory_limit=2G\nmax_execution_time=1800\n' > "$PHP_INI_DIR/conf.d/magento.ini"

COPY --from=composer:2.10.3 /usr/bin/composer /usr/bin/composer

WORKDIR $INSTALL_DIR

# Pre-create the bind-mount target so the named volume is seeded with www-data ownership
# (otherwise Docker creates app/, app/code/ and app/code/Sezzle/ as root)
RUN chsh -s /bin/bash www-data \
    && mkdir -p "$INSTALL_DIR/app/code/Sezzle/Sezzlepay" \
    && chown -R www-data:www-data /var/www

COPY ./process /usr/local/bin/process
RUN chmod +x /usr/local/bin/process
