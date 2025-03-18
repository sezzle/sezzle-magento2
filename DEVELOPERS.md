# Setup

https://experienceleague.adobe.com/en/docs/commerce-operations/installation-guide/composer

https://isuruuy.medium.com/configuring-magento2-using-mamp-server-7fbedc35297d

https://www.mageplaza.com/devdocs/how-install-magento-2-mac-osx.html

## Install Elasticsearch

In Terminal, run the following:
```
docker run -d --name opensearch \
  -p 9201:9200 -p 9601:9600 \
  -e "discovery.type=single-node" \
  -e "DISABLE_SECURITY_PLUGIN=true" \
  opensearchproject/opensearch:2.7.0
```

## Install PHP

In Terminal, run the following:
```
brew install php@8.3
brew link --overwrite --force php@8.3
```

## Install MAMP and Magento

[Download MAMP](https://www.mamp.info/en/downloads/)
Unzip the downloaded file, then drag & drop to the `Applications` folder

Note: The following is written for Magento 2.4.7, the latest stable version at the time of writing. You may need to download additional versions to troubleshoot issues on earlier versions, or as later versions become available.

In Terminal, run the following:
```
cd /Applications/MAMP/htdocs
mkdir magento && cd magento
```
### With Composer:

In Terminal, run the following:
```
brew install composer
composer create-project --repository-url=https://repo.magento.com/ magento/project-community-edition=2.4.7 .
```
When prompted for credentials, use `Magento 2 Keys` from 1Password Dev Vault

### Direct from Magento

Download the zip file for the latest Magento version (in this case 2.4.7-p4): https://github.com/magento/magento2/releases
Unzip the file in your Downloads folder and rename `247`
Drag & Drop the file into /Applications/MAMP/htdocs/magento

### Then
In MAMP:
Select `Web server`: `Apache` and `PHP version`: `8.3.14`
Click Preferences
In the `Ports` tab, set `Apache Port` and `Nginx Port` to `8888` and `MySQL Port` to `8889`
In the `Server` tab, select `Use MySQL server`: `5.7.88`
For `Document Root`, click `Choose` and navigate to `Applications › MAMP › htdocs › magento › 247 > pub`. Click `Choose` to save.
Click `OK`
Click `Start`

## Create Database

In DBeaver or equivalent:
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

## Install Sezzle Extension

### Composer

*For more information about installing Magento with Composer, [click here](https://experienceleague.adobe.com/en/docs/commerce-operations/installation-guide/composer)

In Terminal, run the following:
```
composer require sezzle/sezzlepay
```

### Manual Installation

In Terminal, run the following:
```
cd app/code && mkdir Sezzle
git clone https://github.com/sezzle/sezzle-magento2.git Sezzlepay
cd ../../..
```

## Configure

In Terminal, run the following:
```
bin/magento setup:install \
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

Then also:
```
php bin/magento setup:config:set \
--db-host=127.0.0.1:8889 \
--db-name=magento \
--db-user=root \
--db-password=root
```

Then also:
```
php bin/magento module:enable Sezzle_Sezzlepay
php bin/magento setup:install
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy
```

# Local Testing

Open Docker and start `opensearch` container
Open MAMP and click Start
Open DBeaver and ensure `mamp localhost:8889` database is connected
Navigate to 127.0.0.1:8888/admin