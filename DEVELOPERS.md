# Magento 2 Local setup

*The following documentation is written for Sezzle internal developers only. Merchant developers should refer to README.md for instructions.*

## Prerequisites

### MAMP

1. [Download MAMP](https://www.mamp.info/en/downloads/)
1. Unzip the downloaded file, then drag & drop to the `Applications` folder

### Composer

`brew install composer`

### OpenSearch

In Terminal, run the following:
```
docker run -d --name opensearch \
  -p 9201:9200 -p 9601:9600 \
  -e "discovery.type=single-node" \
  -e "DISABLE_SECURITY_PLUGIN=true" \
  opensearchproject/opensearch:2.7.0
```

### PHP

1. `open ~/.zshrc`
1. Add the following, and save: `export PATH=/Applications/MAMP/Library/bin:/Applications/MAMP/bin/php/php8.3.14/bin:$PATH`
    - Note: php version should correspond to the one selected in MAMP in the later step
2. `source ~/.zshrc`

## Initial setup

### Install Magento to MAMP

In Terminal, run the following:
```
cd /Applications/MAMP/htdocs
mkdir magento && cd magento && mkdir 247 && cd 247
composer create-project --repository-url=https://repo.magento.com/ magento/project-community-edition=2.4.7 .
```

### Install Sezzle Extension

In Terminal, run the following:
```
cd generated/code && mkdir Sezzle
git clone ssh://git@gitlab.sezzle.com:10022/Frontend/magento2AppFrontends.git Sezzlepay
cd ../../..
```

### Configure MAMP

1. Open MAMP app
1. Select `Web server`: `Apache` and `PHP version`: `8.3.14`
1. Click Preferences
1. In the `Ports` tab, set `Apache Port` and `Nginx Port` to `8888` and `MySQL Port` to `8889`
1. In the `Server` tab, select `Use MySQL server`: `8.0.40`
1. For `Document Root`, click `Choose` and navigate to `Applications › MAMP › htdocs › magento › 247 > pub`. Click `Choose` to save.
1. Click `OK`
1. Click `Start`

### Create Database

*Ensure other MySQL instances are not running.*

1. Open DBeaver or equivalent
1. Click `New Database connection`
1. Select `MySQL`, then click `Next`
1. `Port` should be `8889`
1. `Username` and `Password` should each be `root`
1. Click `Finish`
1. Secondary-click on the newly created connection and select `Rename`
1. Enter `mamp` then click `OK`
1. Click the connection to expand, then Secondary-click on `Databases` and select `Create New Database`
1. Enter `Database name`: `magento` then click `OK`

### Configure Magento

In Terminal, run the following:
```
php -d memory_limit=-1 bin/magento setup:install \
--base-url=http://127.0.0.1:8888 \
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
 - Should result in `[SUCCESS]: Magento installation complete.`

Then: `php -d memory_limit=-1 bin/magento sampledata:deploy`
 - When prompted for credentials, use `Magento 2 Keys` in 1Password (Platform Integrations Team vault)
 - Alternatively, [generate new keys](https://www.youtube.com/live/HpwsbgqSR2g). (credentials are `Magento Partner Account in 1Password Dev vault - 2FA sent to magento@sezzle.com, submit an ITSD request to obtain access)
When prompted to store credentials, say `Y`
 - Should result in `Sample data modules have been added via composer.`

In Terminal, run the following:
```
php -d memory_limit=-1 bin/magento module:disable Magento_TwoFactorAuth Magento_AdminAdobeImsTwoFactorAuth
php -d memory_limit=-1 bin/magento setup:upgrade
php -d memory_limit=-1 bin/magento setup:di:compile
php -d memory_limit=-1 bin/magento setup:static-content:deploy -f
php -d memory_limit=-1 bin/magento indexer:reindex
php -d memory_limit=-1 bin/magento cache:clean
```
 - Success messages should be as follows:
  - `The following modules have been disabled:`
  - `Nothing to import.`
  - `Generated code and dependency injection configuration successfully.`
  - `Execution time:`
  - `Stores Feed index has been rebuilt successfully`
  - `Cleaned cache types:`

### Enable mod_rewrite for Apache

1. `cd /Applications/MAMP/conf/apache`
1. `open .`
1. Secondary-click on `httpd.conf` and select `Open With` > `TextEdit.app`
1. Search the document for `#LoadModule rewrite_module modules/mod_rewrite.so` and remove the `#` at the beginning of the line

### Sezzle Configuration

1. Navigate to 127.0.0.1:8888/admin
1. Log in with Username `admin` and Password `admin123`
1. Go to Stores > Configuration > Sales > Payment Methods > Additional Payment Solutions
1. Next to Sezzle, click `Configure`
1. Click `I've already setup Sezzle, I want to edit my settings`
1. Select the following:
1. Change `Enabled` to `Yes`
1. Enter `Public Key` and `Private Key`
    - Can use any valid Sezzle API key pair for testing
2. Click `Save config`

### Creating a Product

1. Go to Catalog > Products
1. Click `Add Product`
1. Required Fields:
  `Enable Product`: `Yes`
  `Product Name`: (any)
  `Price`: (any)
  `Quantity`: (any)
  `Visibility`: `Catalog, Search`
1. Click `Save`
2. Go to `Content` > `Pages`
3. On the line for `Home Page`, click `Select` > `Edit`
4. Expand `Content` then click `Edit with Page Builder` (a red line will appear where it will be inserted)
5. Under `Layout`, drag `Row` to the working area (under the existing snippet)
6. Under `Add Content`, drag `Products` to inside the `Row`
7. Hover over `Products`, then click `Settings` (gear icon)
8.  Select `Category` as `Default Category`, then click `Save`
9. Click `Save as Template`, name the template, then click `Save`
10. Click `Apply template`, then on the template you just saved, click `Apply`
11. Click `OK`
12. Click the `Minimize Window` icon (diagonal arrows, pointing inward)
13. Click `Save`

# Local Testing

1. Open Docker and start `opensearch` container
1. Open MAMP and click Start
1. Open DBeaver and ensure `mamp localhost:8889` database is connected
1. Navigate to `127.0.0.1:8888/admin`
1. All development work will be completed inside `/Applications/MAMP/htdocs/magento/247/generated/code/Sezzle/Sezzlepay` as you would normally in `~/go/src/sezzle/magento2AppFrontends`
  - Gitlab project magento2AppFrontends will mirror push to Github magento2 project for merchant use.
  - Use `php -d memory_limit=-1 bin/magento setup:upgrade` if any changes to Database
  - Use `php -d memory_limit=-1 bin/magento setup:di:compile` if making changes to dependencies
  - Use `php -d memory_limit=-1 bin/magento setup:static-content:deploy -f` if making changes to html, css, or js files

# External Resources

https://experienceleague.adobe.com/en/docs/commerce-operations/installation-guide/composer

https://isuruuy.medium.com/configuring-magento2-using-mamp-server-7fbedc35297d

https://www.mageplaza.com/devdocs/how-install-magento-2-mac-osx.html