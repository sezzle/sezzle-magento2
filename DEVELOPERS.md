# Magento 2 Local setup

*The following documentation is written for Sezzle internal developers only. Merchant developers should refer to README.md for instructions.*

## Prerequisites

### Docker

All Sezzle developers should already have completed [Docker](https://gitlab.sezzle.com/sezzle/sezzle-compose) setup for Sezzle-Compose (although Sezzle-Compose will not be used for Magento setup).

## Method 1: Using Docker

This method uses the [markshust/docker-magento](https://github.com/markshust/docker-magento) setup script to quickly provision a Magento 2 development environment.

### Prerequisites

1. **Stop conflicting services**: This Docker setup creates its own database and Redis containers whose ports clash with MySQL and Redis from Sezzle-Compose. Before proceeding, stop your MySQL and Redis containers created from Sezzle-Compose.

2. **Configure Docker file sharing**:
   - Add `Sites` directory to Docker file sharing settings
   - Add `.composer` directory to Docker file sharing settings

### Installation Steps

1. **Create the Magento directory**:
   ```bash
   mkdir -p Sites/magento
   cd Sites/magento
   ```

2. **Run the one-line setup script**:
   ```bash
   curl -s https://raw.githubusercontent.com/markshust/docker-magento/master/lib/onelinesetup | bash -s -- magento.test community 2.4.8-p3
   ```
   - This will prompt for your system password to add `magento.test` to `/etc/hosts` and install CA certificate
   - When prompted for Magento access keys, retrieve them from 1Password under "Magento 2 Access Keys"

3. **Complete initialization** (if installation stopped mid-way):
   ```bash
   chmod +x ./bin/init
   ./bin/init
   ```

4. **Disable Two-Factor Authentication**:
   ```bash
   bin/magento module:disable Magento_TwoFactorAuth Magento_AdminAdobeImsTwoFactorAuth
   ```

5. **Install Sezzle Extension**:
   ```bash
   cd src/app/code/
   mkdir -p sezzle
   cd sezzle
   git clone ssh://git@gitlab.sezzle.com:10022/Frontend/magento2AppFrontends.git sezzlepay
   cd ../../..
   ```

6. **Enable and compile the module**:
   ```bash
   bin/magento module:enable Sezzle_Sezzlepay
   bin/magento setup:upgrade
   bin/magento setup:di:compile
   ```

7. **Deploy sample data** (optional):
   - Sample products should be added during the initial setup. If they weren't, run the following command from the Magento root directory:
   ```bash
   bin/magento sampledata:deploy
   ```

### Access Information

After successful installation, you can access the Magento admin panel:

- **Admin URL**: `magento.test/admin`
- **Username**: `john.smith`
- **Password**: `password123`

## Method 2: Using MAMP

1. [Download MAMP](https://www.mamp.info/en/downloads/)
1. Unzip the downloaded file, then drag & drop to the `Applications` folder

### PHP

1. `open ~/.zshrc`
1. Add the following, and save: `export PATH=/Applications/MAMP/Library/bin:/Applications/MAMP/bin/php/php8.3.14/bin:$PATH`
    - Note: php version should correspond to the one selected in MAMP in the later step
1. `source ~/.zshrc`

### Enable mod_rewrite for Apache

*This section will resolve the issue where stylesheets aren't loading for test environment*

1. `cd /Applications/MAMP/conf/apache`
1. `open .`
1. Secondary-click on `httpd.conf` and select `Open With` > `TextEdit.app`
1. Search the document for `#LoadModule rewrite_module modules/mod_rewrite.so` and remove the `#` at the beginning of the line

### Composer

`brew install composer`

### OpenSearch <!-- or Elasticsearch -->

In Terminal, run the following:
```
docker run -d --name opensearch \
  -p 9201:9200 -p 9601:9600 \
  -e "discovery.type=single-node" \
  -e "DISABLE_SECURITY_PLUGIN=true" \
  opensearchproject/opensearch:2.7.0
```

<!-- 
This will appear in the Magento config in a future step:
--search-engine=opensearch \
--opensearch-host=localhost \
--opensearch-port=9201 \
--opensearch-index-prefix=magento2 \
--opensearch-timeout=15 \
 -->

<!-- Elasticsearch was the original product recommended by Magento. Either product will work, but now OpenSearch is recommended by Magento/Adobe:
```
docker run -d --name elasticsearch \
  -p 9200:9200 -p 9601:9600 \
  -e "discovery.type=single-node" \
  docker.elastic.co/elasticsearch/elasticsearch:7.17.28
```
When using this method, update the Magento configuration below accordingly.

--search-engine=elasticsearch7 \
--elasticsearch-host=127.0.0.1 \
--elasticsearch-port=9200 \
 -->

## Initial setup

<!-- ### Docker Version

```bash
docker exec -it sezzle_magento2 process install
docker exec -it sezzle_magento2 process install-sampledata
docker exec -it sezzle_magento2 process upgrade
docker exec -it sezzle_magento2 process compile
docker exec -it sezzle_magento2 process deploy
docker exec -it sezzle_magento2 process developer
docker exec -it sezzle_magento2 process clear
docker-compose down --rmi local -v --remove-orphans
docker-compose up -d --build
```

Open `localhost:8085`  
-->
### Install Magento to MAMP

In Terminal, run the following:
```
cd /Applications/MAMP/htdocs
mkdir magento && cd magento && mkdir 248 && cd 248
composer create-project --repository-url=https://repo.magento.com/ magento/project-community-edition=2.4.8 .
```

### Configure MAMP

1. Open MAMP app
1. Select `Web server`: `Apache` and `PHP version`: `8.3.14`
1. Click Preferences
1. In the `Ports` tab, set `Apache Port` and `Nginx Port` to `8888` and `MySQL Port` to `8889`
1. In the `Server` tab, select `Use MySQL server`: `8.0.40`
1. For `Document Root`, click `Choose` and navigate to `Applications › MAMP › htdocs › magento › 248 > pub`. Click `Choose` to save.
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

*At this point, you should be able to open 127.0.0.1:8888 and 127.0.0.1:8888/admin but not log in*

In Terminal, run the following:
```
php -d memory_limit=-1 bin/magento module:disable Magento_TwoFactorAuth Magento_AdminAdobeImsTwoFactorAuth
```

*At this point, you should be able to open 127.0.0.1:8888/admin and log in, but Sezzle will not be available in Payment Methods*

### Install Sezzle Extension

In Terminal, run the following:
```
cd vendor && mkdir sezzle && cd sezzle && git clone ssh://git@gitlab.sezzle.com:10022/Frontend/magento2AppFrontends.git sezzlepay
cd ../..
```

*At this point, if you run the [Compile](#compile) command cluster below, you should be able to see Sezzle as an option  on [127.0.0.1:8888/admin](http://127.0.0.1:8888/admin/admin/system_config/edit/key/460ee844e615c1955534bea89954c0b3fbb24487d8c9e5835699e9920f8a3421/section/payment/), but you will not be able to add API keys*

*Note: Getting `Unknown module(s)` error during `module:enable`? Try this instead:*

```
cd app && mkdir code && cd code && mkdir sezzle && cd sezzle && git clone ssh://git@gitlab.sezzle.com:10022/Frontend/magento2AppFrontends.git sezzlepay
cd ../../..
```

### Sample Data

In Terminal, run the following: `php -d memory_limit=-1 bin/magento sampledata:deploy`
 - When prompted for credentials, use `Magento 2 Keys` in 1Password (Platform Integrations Team vault)
 - Alternatively, [generate new keys](https://www.youtube.com/live/HpwsbgqSR2g). (credentials are `Magento Partner Account` in 1Password Dev vault - 2FA sent to magento@sezzle.com, submit an ITSD request to obtain access)
When prompted to store credentials, say `Y`
 - Should result in `Sample data modules have been added via composer.`

### Compile

In Terminal, run the following:
```
php -d memory_limit=-1 bin/magento module:enable Sezzle_Sezzlepay
php -d memory_limit=-1 bin/magento setup:upgrade
php -d memory_limit=-1 bin/magento setup:di:compile
php -d memory_limit=-1 bin/magento setup:static-content:deploy -f
php -d memory_limit=-1 bin/magento indexer:reindex
php -d memory_limit=-1 bin/magento cache:clean
```

*At this point, you should be able to see sample products on the storefront*

### Sezzle Configuration

1. Navigate to `127.0.0.1:8888/admin`
1. Log in with Username `admin` and Password `admin123`
1. Go to `Stores` > `Configuration` > `Sales` > `Payment Methods` > `Additional Payment Solutions`
1. Next to `Sezzle`, click `Configure`
1. Click `I've already setup Sezzle, I want to edit my settings`
1. Change `Enabled` to `Yes`
1. Enter `Public Key` and `Private Key`
    - Can use any valid sandbox Sezzle API key pair for testing (recommended: [Sezzle Shopify Test Store](https://sandbox.admin.sezzle.com/merchants/75097))
    - The API keys validation only confirms that a merchant was found with the provided public and private keys. It does not validate the shop url is correct, hence how merchants re-use API keys across multiple stores. 
    - This creates a nightmare for accounting, because the orders are recorded under the one account without distinction of site origin. 
    - It also affects widgets, since the API Keys are used to generate the UUID in the widget snippet. Not only do we not know that widgets are installed on the other stores, but config management also gets messy.
2. Click `Save config`

### Populating the store

*This section is in case of error generated [Sample Data](#sample-data)*

#### Creating a product

1. Go to `Catalog` > `Products`
1. Click `Add Product`
1. Required Fields:
    - `Enable Product`: `Yes`
    - `Product Name`: (any)
    - `Price`: (any)
    - `Quantity`: (any)
    - `Category`: `Default Category`
    - `Visibility`: `Catalog, Search`
1. Click `Save`

#### Adding Products to Home Page Template

1. Go to `Content` > `Pages`
1. On the line for `Home Page`, click `Select` > `Edit`
1. Expand `Content` then click `Edit with Page Builder` (a red line will appear where it will be inserted)
1. Under `Layout`, drag `Row` to the working area (under the existing snippet)
1. Under `Add Content`, drag `Products` to inside the `Row`
1. Hover over `Products`, then click `Settings` (gear icon)
1. Select `Category` as `Default Category`, then click `Save`
1. Click `Save as Template`, name the template, then click `Save`
1. Click `Apply template`, then on the template you just saved, click `Apply`
1. Click `OK`
1. Click the `Minimize Window` icon (diagonal arrows, pointing inward)
1. Click `Save`

# Local Testing

1. Open Docker Desktop and start `opensearch` container
1. Open MAMP, update `Document root`, then click `Start`
    - Click `Preferences`.
    - In the `Server` tab under `Document Root`, click `Choose` and navigate to `Applications › MAMP › htdocs › magento › 248 > pub`. Click `Choose` to save, then click `OK`
1. Open DBeaver and ensure `mamp localhost:8889` database is connected
1. Navigate to `127.0.0.1:8888/admin`
1. All development work will be completed inside `/Applications/MAMP/htdocs/magento/248/vendor/sezzle/sezzlepay` as you would normally in `~/go/src/sezzle/magento2AppFrontends`
    - Gitlab project magento2AppFrontends will mirror push to Github magento2 project for merchant use.
    - Use `php -d memory_limit=-1 bin/magento setup:upgrade` if any changes to Database
    - Use `php -d memory_limit=-1 bin/magento setup:di:compile` if making changes to dependencies
    - Use `php -d memory_limit=-1 bin/magento setup:static-content:deploy -f` if making changes to html, css, or js files

## Releasing Updates to Merchants

### Code Changes

1. Update CHANGELOG.md
1. Update `version` number in `composer.json`
1. Delete previous version zip file
1. `open .`
1. Select all *contents* of magento2AppFrontends and compress, renaming the zip file `sezzle_sezzlepay-{version}.zip`
1. Merge to production

### Magento Submission

1. Log in to https://commercedeveloper.adobe.com/ using `Magento Partner Account` entry in 1Password
1. Click `Extensions`
1.  Click `Extension Name`: `Sezzle` where Platform is `M2`
1. Click `Submit a New Version`
1. Enter `Adobe Commerce Version Number` per the same version reflected in `composer.json`
1. Unless a feature must be released at a specific date, select `Requested Launch Date`: `On Approval`
1. Click `Continue`
1. Click `Attach Package`. Navigate to and select the zipped project folder, then click `Open`
1. Select `Adobe Commerce Version Compatibility`: (all)
1. Copy entry from `CHANGELOG.md` to the `Release Notes` field
1. Click `Submit`

### GitLab Tag

1. Go to https://gitlab.sezzle.com/Frontend/magento2AppFrontends/-/tags
1. Click `New Tag`
1. Enter `Tag Name` as the version number, i.e. `v7.0.23`
1. In the `Create From` dropdown, select the branch which contains the updated Changelog.md and .zip file for the release
1. Enter a message, if desired
1. Click `Create Tag`

Alternatively, this can be accomplished via CLI, but only after the feature branch has been merged to production:

*Update the version number and comment below as applicable*
```
git checkout production && git pull origin production
git tag -a vA.B.C -m "<comment>"
git push origin vA.B.C
```

### GitHub Release

Gitlab magento2AppFrontends project will automatically mirror to Github sezzle-magento2 project. Once that has occurred AND the Adobe Marketplace submission has been approved, the following steps must be completed manually

Go to https://github.com/sezzle/sezzle-magento2/releases
1. Click `Draft a new release`
1. Click `Choose a tag`, then select the applicable version
1. Enter `Release Title`: `Version {version}`
1. In `Decribe this release`, copy + paste the CHANGELOG.md entry
    - No need to add attachments, the zip file will be added automatically.
1. Ensure `Set as the latest release` is checked, then click `Publish release`

*Note: Do not create the tag in Github only, as it will be deleted during the next Gitlab mirror, and the release will revert to Draft*

<!-- to do: troubleshooting guide -->
# External Resources

https://experienceleague.adobe.com/en/docs/commerce-operations/installation-guide/composer

https://isuruuy.medium.com/configuring-magento2-using-mamp-server-7fbedc35297d

https://www.mageplaza.com/devdocs/how-install-magento-2-mac-osx.html

# Allowlisting Merchants

Under `In-Context Settings`, merchants will see instructions to `Make sure you are approved by Sezzle for the InContext Checkout Solution to work.` If they wish to select Checkout Mode: `iframe`, they should contact us to perform the following:

In `sezzle-checkout/deploy/default`, update `CONTENT_SECURITY_POLICY` for each applicable environment by appending the merchant's URL(s) to the `value`

Post the MR in the `#code-review-checkout` Slack channel for approval.

_If they select Checkout Mode: `popup`, this is not necessary._