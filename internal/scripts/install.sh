#!/bin/sh

mysql_ready() {
    mysqladmin ping -h $MYSQL_HOST -u $MYSQL_USER -p$MYSQL_PASSWORD > /dev/null 2>&1
}

while !(mysql_ready)
do
    sleep 3
    echo "waiting for mysql database to be ready..."
done

COMPOSER_HOME=/var/www/.composer

echo "Installing Magento $MAGENTO_VERSION..."
./bin/magento setup:install \
    --db-host=$MYSQL_HOST \
    --db-name=$MYSQL_DATABASE \
    --db-user=$MYSQL_USER \
    --db-password=$MYSQL_PASSWORD \
    --base-url=$MAGENTO_URL \
    --base-url-secure=$MAGENTO_BASE_URL_SECURE \
    --use-secure=$MAGENTO_USE_SECURE \
    --use-secure-admin=$MAGENTO_USE_SECURE_ADMIN \
    --backend-frontname=$MAGENTO_BACKEND_FRONTNAME \
    --language=$MAGENTO_LOCALE \
    --timezone=$MAGENTO_TIMEZONE \
    --currency=$MAGENTO_DEFAULT_CURRENCY \
    --admin-firstname=$MAGENTO_ADMIN_FIRSTNAME \
    --admin-lastname=$MAGENTO_ADMIN_LASTNAME \
    --admin-email=$MAGENTO_ADMIN_EMAIL \
    --admin-user=$MAGENTO_ADMIN_USERNAME \
    --admin-password=$MAGENTO_ADMIN_PASSWORD \
    --use-rewrites=1

echo "Setting up developer mode..."
./bin/magento deploy:mode:set developer

echo "Enabling sezzle module..."
./bin/magento module:enable Sezzle_Sezzlepay
./bin/magento setup:upgrade
./bin/magento setup:di:compile
./bin/magento setup:static-content:deploy -f
./bin/magento indexer:reindex
./bin/magento cache:flush