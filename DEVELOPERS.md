# Magento 2 Local setup

## Prerequisites

### MAMP
[Download MAMP](https://www.mamp.info/en/downloads/)
Unzip the downloaded file, then drag & drop to the `Applications` folder

### Composer
`brew install composer`

### OpenSearch

```
docker run -d --name opensearch \
  -p 9201:9200 -p 9601:9600 \
  -e "discovery.type=single-node" \
  -e "DISABLE_SECURITY_PLUGIN=true" \
  opensearchproject/opensearch:2.7.0
```

### PHP

`open ~/.zshrc`
Add the following, and save: `export PATH=/Applications/MAMP/Library/bin:/Applications/MAMP/bin/php/php8.3.14/bin:$PATH`
`source .zshrc`

## Initial setup

### Install Magento to MAMP

```
cd /Applications/MAMP/htdocs
mkdir magento && cd magento && mkdir 247 && cd 247
composer create-project --repository-url=https://repo.magento.com/ magento/project-community-edition=2.4.7 .
```

### Configure MAMP

Open MAMP app
Select `Web server`: `Apache` and `PHP version`: `8.3.14`
Click Preferences
In the `Ports` tab, set `Apache Port` and `Nginx Port` to `8888` and `MySQL Port` to `8889`
In the `Server` tab, select `Use MySQL server`: `5.7.88`
For `Document Root`, click `Choose` and navigate to `Applications › MAMP › htdocs › magento › 247 > pub`. Click `Choose` to save.
Click `OK`
Click `Start`

### Create Database

*Ensure other MySQL instances are not running.*

Open DBeaver or equivalent
Click `New Database connection`
Select `MySQL`, then click `Next`
Change `Port` to `8889`
`Username` and `Password` should each be `root`
Click `Finish`
Secondary-click on the newly created connection and select `Rename`
Enter `mamp` then click `OK`
Secondary-click on the connection again, select `SQL Editor` then `New SQL Script`
Enter the following SQL into the editor, then click the `Execute SQL Query` button (orange right arrow)
```
CREATE DATABASE `magento`;
GRANT ALL PRIVILEGES ON magento.* TO 'root'@'localhost';
FLUSH PRIVILEGES;
```

### Configure Magento

```
php -d memory_limit=-1 bin/magento setup:install \
--base-url=http://127.0.0.1:8888/ \
--db-host=127.0.0.1:8889 \
--db-name=magento \
--db-user=root \
--db-password=root \
--admin-firstname=admin \
--admin-lastname=admin \
--admin-email=admin@admin.com \
--admin-user=admin \
--admin-password=admin123 \
--language=en_US \
--currency=USD \
--timezone=America/Chicago \
--use-rewrites=1 \
--search-engine=opensearch \
--opensearch-host=localhost \
--opensearch-port=9201 \
--opensearch-index-prefix=magento2 \
--opensearch-timeout=15 \
--backend-frontname=admin
```

`php -d memory_limit=-1 bin/magento sampledata:deploy`
When prompted for credentials, use `Magento 2 Keys` in 1Password

```
php -d memory_limit=-1 bin/magento module:disable Magento_TwoFactorAuth Magento_AdminAdobeImsTwoFactorAuth
php -d memory_limit=-1 bin/magento setup:upgrade
php -d memory_limit=-1 bin/magento setup:di:compile
php -d memory_limit=-1 bin/magento setup:static-content:deploy -f
php -d memory_limit=-1 bin/magento indexer:reindex
php -d memory_limit=-1 bin/magento cache:clean
```

# Local Testing

Open Docker and start `opensearch` container
Open MAMP and click Start
Open DBeaver and ensure `mamp localhost:8889` database is connected
Navigate to 127.0.0.1:8888/admin

# Resources

https://experienceleague.adobe.com/en/docs/commerce-operations/installation-guide/composer

https://isuruuy.medium.com/configuring-magento2-using-mamp-server-7fbedc35297d

https://www.mageplaza.com/devdocs/how-install-magento-2-mac-osx.html