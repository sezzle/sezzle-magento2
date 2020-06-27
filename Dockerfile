FROM alexcheng/magento2

LABEL maintainer="sezzle"
LABEL php_version="7.3.12"
LABEL magento_version="2.3.3"
LABEL description="Magento 2.3.3 with PHP 7.3.12"

WORKDIR $INSTALL_DIR

ENV SEZZLE_PATH app/code/Sezzle/Sezzlepay

RUN chsh -s /bin/bash www-data

RUN mkdir -p $SEZZLE_PATH

WORKDIR $SEZZLE_PATH

ADD . .

COPY ./process /usr/local/bin/process
RUN chmod +x /usr/local/bin/process

WORKDIR $INSTALL_DIR

VOLUME $INSTALL_DIR
