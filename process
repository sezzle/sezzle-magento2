#!/bin/bash


su www-data <<EOSU

NOCOLOR='\033[0m'
GREEN='\033[0;32m'

Install () {
    echo "Installing Magento 2...."
    bin/magento setup:install --base-url=$MAGENTO_URL --backend-frontname=$MAGENTO_BACKEND_FRONTNAME --language=$MAGENTO_LANGUAGE --timezone=$MAGENTO_TIMEZONE --currency=$MAGENTO_DEFAULT_CURRENCY --db-host=$MYSQL_HOST --db-name=$MYSQL_DATABASE --db-user=$MYSQL_USER --db-password=$MYSQL_PASSWORD --use-secure=$MAGENTO_USE_SECURE --base-url-secure=$MAGENTO_BASE_URL_SECURE --use-secure-admin=$MAGENTO_USE_SECURE_ADMIN --admin-firstname=$MAGENTO_ADMIN_FIRSTNAME --admin-lastname=$MAGENTO_ADMIN_LASTNAME --admin-email=$MAGENTO_ADMIN_EMAIL --admin-user=$MAGENTO_ADMIN_USERNAME --admin-password=$MAGENTO_ADMIN_PASSWORD
    echo "Installed Magento 2 successfully...."
}

Setup () {
    echo "Setting up Magento 2...."
    bin/magento s:upgrade
    echo "Setup successful...."
}

Compile () {
    echo "Compilation started...."
    bin/magento s:d:co
    echo "Compiled successfully...."
}

Deploy () {
    echo "Deployment started...."
    bin/magento s:s:de -f
    echo "Deployment successful...."
}

CacheClean () {
    echo "Clearing the cache...."
    bin/magento c:f
    echo "Cache cleared successfully...."
}

SetDeveloperMode () {
    echo "Activating developer mode...."
    bin/magento d:m:se developer
    echo "Developer mode activated successfully...."
}

if [ "$1" == "install" ]
then
    Install
    SetDeveloperMode
    Setup
    Compile
    Deploy
    CacheClean
elif [ "$1" == "developer" ]
then
    SetDeveloperMode
elif [ "$1" == "upgrade" ]
then
    Setup
elif [ "$1" == "compile" ]
then
    Compile
elif [ "$1" == "deploy" ]
then
    Deploy
elif [ "$1" == "clear" ]
then
    CacheClean
else
    echo "command not found"
fi

EOSU
