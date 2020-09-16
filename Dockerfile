FROM trafex/alpine-nginx-php7

ARG MAGENTO_VERSION=2.3.5

ENV INSTALL_DIR /var/www/html
ENV COMPOSER_HOME /var/www/.composer

USER root

RUN apk --no-cache add less mysql-client sudo \
    php7-simplexml php7-pdo_mysql php7-iconv php7-mcrypt php7-soap php7-xsl php7-bcmath php7-zip php7-xmlwriter php7-tokenizer

RUN echo "memory_limit=2048M" > /etc/php7/conf.d/memory-limit.ini

RUN rm -f *

RUN curl -sS https://getcomposer.org/installer | php && \
    mv composer.phar /usr/local/bin/composer

RUN mkdir -p $COMPOSER_HOME && chown -R nobody:nobody $COMPOSER_HOME/

COPY ./internal/scripts/nginx.conf /etc/nginx/nginx.conf
COPY ./internal/scripts/install.sh /usr/local/bin/install
COPY ./internal/scripts/process.sh /usr/local/bin/process
RUN chmod +x /usr/local/bin/install
RUN chmod +x /usr/local/bin/process

USER nobody

WORKDIR /tmp

# https://github.com/magento/magento2/releases
RUN curl https://codeload.github.com/magento/magento2/tar.gz/$MAGENTO_VERSION -o magento2.tar.gz

# https://github.com/magento/magento2-sample-data/releases
RUN curl https://codeload.github.com/magento/magento2-sample-data/tar.gz/$MAGENTO_VERSION -o magento2-sample-data.tar.gz

RUN tar xf magento2.tar.gz && \
    mv magento2-$MAGENTO_VERSION/* magento2-$MAGENTO_VERSION/.htaccess $INSTALL_DIR

RUN tar xf magento2-sample-data.tar.gz && \
    mkdir -p $INSTALL_DIR/sample-data && \
    mv magento2-sample-data-$MAGENTO_VERSION/* $INSTALL_DIR/sample-data

RUN rm -rf /tmp/magento*

WORKDIR $INSTALL_DIR

RUN php -f sample-data/dev/tools/build-sample-data.php -- --ce-source=$INSTALL_DIR

RUN composer update

RUN find . -type d -exec chmod 770 {} \; \
    && find . -type f -exec chmod 660 {} \; \
    && chmod u+x bin/magento
