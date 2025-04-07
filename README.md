<div align="center">
    <a href="https://sezzle.com">
        <img src="https://media.sezzle.com/branding/2.0/Sezzle_Logo_FullColor.svg" width="300px" alt="Sezzle" />
    </a>
</div>

# Sezzle Extension for Magento 2

## Introduction

This document will help you in installing `Sezzle's Magento 2` extension. This extension is a certified one and listed [here](https://marketplace.magento.com/sezzle-sezzlepay.html) in the Marketplace. The plugin can also be downloaded from [Github](https://github.com/sezzle/sezzle-magento2).

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
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy
php bin/magento indexer:reindex
php bin/magento cache:clean
```

### Manual

1. Download the .zip or tar.gz file from [Sezzle's Github repository](https://github.com/sezzle/sezzle-magento2/blob/production/sezzle_sezzlepay-7.0.20.zip).
2. Unzip the file, rename to `sezzlepay`, then drag & drop to `[Magento]/vendor/sezzle/`.
3. In Terminal, run the following:
```
php bin/magento module:enable Sezzle_Sezzlepay
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy
php bin/magento indexer:reindex
php bin/magento cache:clean
```

## How to upgrade the extension?

### Composer

1. Open terminal and navigate to `[Magento]` root path.
```
composer update sezzle/sezzlepay
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy
php bin/magento indexer:reindex
php bin/magento cache:clean
```

### Manual

Repeat the manual installation instructions above, overwriting the existing content

## Configure Sezzle

Log in to Magento Admin and complete the configuration per the instructions [here](https://docs.sezzle.com/docs/plugins/magento-2).

*Your store is now ready to accept payments through Sezzle.*