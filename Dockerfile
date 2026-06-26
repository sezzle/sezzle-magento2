FROM alexcheng/magento2

LABEL maintainer="sezzle"
LABEL php_version="8.4.1"
LABEL magento_version="2.4.9"
LABEL description="Magento 2.4.9 with PHP 8.4.1"

WORKDIR $INSTALL_DIR

RUN chsh -s /bin/bash www-data

COPY ./process /usr/local/bin/process
RUN chmod +x /usr/local/bin/process
