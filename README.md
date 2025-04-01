<div align="center">
    <a href="https://sezzle.com">
        <img src="https://media.sezzle.com/branding/2.0/Sezzle_Logo_FullColor.svg" width="300px" alt="Sezzle" />
    </a>
</div>

# Sezzle Extension for Magento 2

## Introduction

This document will help you in installing `Sezzle's Magento 2` extension. This extension is a certified one and listed [here](https://marketplace.magento.com/sezzle-sezzlepay.html) in the marketplace. The plugin can also be downloaded from [github](https://github.com/sezzle/sezzle-magento2).

## How to install the extension?

There are two ways of installing and upgrading the extension. 
1. **Composer** is the quick installation method which is also easier to update.
2. **Manual** is useful for development.

*For all purposes assume `[Magento]` as your Magento 2 root directory.*

### Composer

Open terminal and navigate to `Magento` root path.
```
brew install composer
composer require sezzle/sezzlepay
php -d memory_limit=-1 bin/magento setup:upgrade
php -d memory_limit=-1 bin/magento setup:di:compile
php -d memory_limit=-1 bin/magento setup:static-content:deploy -f
php -d memory_limit=-1 bin/magento indexer:reindex
php -d memory_limit=-1 bin/magento cache:clean
```

### Manual

1. Download the .zip or tar.gz file from [Sezzle's Github repository](https://github.com/sezzle/sezzle-magento2/blob/production/sezzle_sezzlepay-7.0.20.zip).
2. Unzip the file, then drag & drop to `[Magento]/app/code/` either through `SFTP` or `SSH`.
3. Copy `Sezzle` directory from unzipped folder to `[Magento]/vendor/sezzle/`.
4. In Terminal, run the following:
```
php bin/magento module:enable Sezzle_Sezzlepay
php -d memory_limit=-1 bin/magento setup:upgrade
php -d memory_limit=-1 bin/magento setup:di:compile
php -d memory_limit=-1 bin/magento setup:static-content:deploy -f
php -d memory_limit=-1 bin/magento indexer:reindex
php -d memory_limit=-1 bin/magento cache:clean
```

## How to upgrade the extension?

### Composer

1. Open terminal and navigate to `[Magento]` root path.
```
composer update sezzle/sezzlepay
php -d memory_limit=-1 bin/magento setup:upgrade
php -d memory_limit=-1 bin/magento setup:di:compile
php -d memory_limit=-1 bin/magento setup:static-content:deploy -f
php -d memory_limit=-1 bin/magento indexer:reindex
php -d memory_limit=-1 bin/magento cache:clean
```

### Manual

Repeat the manual installatin instructions above, overwriting the existing content

## Configure Sezzle

Log in to Magento Admin and complete the configuration per the instructions [here](Log in to Magento Admin and complete the configuration per the instructions [here](https://docs.sezzle.com/docs/plugins/magento-2).
).

*Your store is now ready to accept payments through Sezzle.*

## How Sandbox works?

1. In the `Sezzle` configuration page of your `Magento` admin, enter the `Sandbox` `API Keys` from your [`Sezzle Merchant Sandbox Dashboard`](https://sandbox.dashboard.sezzle.com/merchant/) and set the `Payment Mode` to `Sandbox`, then save the configuration. Make sure you are doing this on your `dev/staging` website.
1. On your website, add an item to the cart, then proceed to `Checkout` and select `Sezzle` as the payment method.
1. To pay with Sezzle:
    1. If customer is not tokenized, click `Continue to Sezzle`.
    1. If customer is tokenized, click `Place Order`. However, if the customer tokenization is expired, Sezzle will create a new checkout on clicking `Place Order`.
    1. If In-Context checkout, click `Pay with Sezzle`.
1. For In-Context checkout, the Sezzle checkout will be hosted in the configured mode, `iFrame` or `Popup`. Otherwise, you will be redirected to the Sezzle checkout.
1. Sign In or Sign Up to continue.
1. Enter the payment details using test data, then move to final page.
1. Check the `Approve {Website Name} to process payments from your Sezzle account for future transactions. You may revoke this authorization at any time in your Sezzle Dashboard` to tokenize your account.
1. If your account is already tokenized, order will be placed without redirection otherwise you will be redirected to Sezzle Checkout for completing the purchase.
1. After payment is completed at Sezzle, you will be directed to your site's successful payment page.
1. `Sandbox` testing is complete. You can login to your `Sezzle Merchant Sandbox Dashboard` to see the test order you just placed.

## Troubleshooting/Debugging

1. There is logging enabled by `Sezzle` for tracing the `Sezzle` actions.
1. In case merchant is facing issues which is unknown to `Merchant Success` and `Support` team, they can ask for this logs and forward to the `Platform Integrations` team.
1. Name of the log will be `sezzlepay.log`.It is always recommended to send the `system.log` and `exception.log` for better tracing of issues.

<!-- ## Docker Environment Set Up

### Start

1. Clone the repo.
1. Execute `docker-compose up -d --build` to start the Magento server.
1. Server will be up at `localhost:8085`. If you want to change that, edit the `docker-compose.yml`.

### Install Magento

```bash
docker exec -it sezzle_magento2 process install
```

Sezzle will be installed alongside.

### Sample Data Deploy

```bash
docker exec -it sezzle_magento2 process install-sampledata
```

### Database Upgrade

```bash
docker exec -it sezzle_magento2 process upgrade
```

### Compile

```bash
docker exec -it sezzle_magento2 process compile
```

### Deploy Static Files

```bash
docker exec -it sezzle_magento2 process deploy
```

### Set Developer Mode

```bash
docker exec -it sezzle_magento2 process developer
```

### Cache Clear

```bash
docker exec -it sezzle_magento2 process clear
```

### Cleanup Environment

```bash
docker-compose down --rmi local -v --remove-orphans
``` -->
