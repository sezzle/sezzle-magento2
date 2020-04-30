## Sezzle Extension for Magento 2

## Introduction
This document will help you in installing `Sezzle's Magento 2` extension. This extension is a certified one and listed [here](https://marketplace.magento.com/sezzle-sezzlepay.html) in the marketplace. The plugin can also be downloaded from [github](https://github.com/sezzle/sezzle-magento2).

## How to install the extension?

There are two ways of installing and upgrading the extension. 
* By composer.
* Manual Process.

### For all purposes assume [Magento] as your Magento 2 root directory.

### Composer
* Open terminal and navigate to `Magento` root path.
* `composer require sezzle/sezzlepay`
* `php bin/magento setup:upgrade`
* `php bin/magento setup:di:compile`
* `php bin/magento setup:static-content:deploy`
* `php bin/magento cache:clean`

### Manual
* Download the .zip or tar.gz file from `Sezzle's` github repository.
* Unzip the file and follow the following instructions.
* Navigate to `Magento` `[Magento]/app/code/` either through `SFTP` or `SSH`.
* Copy `Sezzle` directory from unzipped folder to `[Magento]/app/code/`.
* Open the terminal.
* Run the below command to enable `Sezzle`:
```php bin/magento module:enable Sezzle_Sezzlepay```
* Run the `Magento` setup upgrade:
```php bin/magento setup:upgrade```
* Run the `Magento` Dependencies Injection Compile:
```php bin/`magento` setup:di:compile```
* Run the `Magento` Static Content deployment:
```php bin/magento setup:static-content:deploy```
* Login to `Magento` Admin and navigate to `System > Cache Management`.
* Flush the cache storage by selecting `Flush Cache Storage`.

You can now directly navigate from the Configuration Page to get signed up for `Sezzle`. To do so, you need to click on `Register for Sezzle` which will redirect you to the `Sezzle Merchant Signup` Page. If you have the details already, you can simply click on ` I've already setup Sezzle, I want to edit my settings` to move ahead.

## How to upgrade the extension?

### Composer
* Open terminal and navigate to `Magento` root path.
* `composer update sezzle/sezzlepay`
* `php bin/magento setup:upgrade`
* `php bin/magento setup:di:compile`
* `php bin/magento setup:static-content:deploy`
* `php bin/magento cache:clean`

### Manual
* Download the .zip or tar.gz file from `Sezzle's` github repository.
* Unzip the file and follow the following instructions.
* Copy `Sezzle` directory from unzipped folder to `[Magento]/app/code/`. Make sure you are overwriting the files.
* Open the terminal.
* Run the below command to enable `Sezzle`:
```php bin/magento module:enable Sezzle_Sezzlepay```
* Run the `Magento` setup upgrade:
```php bin/magento setup:upgrade```
* Run the `Magento` Dependencies Injection Compile:
```php bin/`magento` setup:di:compile```
* Run the `Magento` Static Content deployment:
```php bin/magento setup:static-content:deploy```
* Login to `Magento` Admin and navigate to `System > Cache Management`.
* Flush the cache storage by selecting `Flush Cache Storage`.


## Configure Sezzle

* Make sure you have the `Merchant ID` and the `API Keys` from the `Sezzle Merchant Dashboard`.
* Navigate to `Stores > Configuration > Sales > Payment Methods > Sezzle > Payment Settings` in your `Magento` admin.
* Set the Payment Mode to `Live` for LIVE and set it as `Sandbox` for SANDBOX.
* Set the `Merchant ID`, `Public Key` and `Private Key`.
* Set `Payment Action` as `Authorize only` for doing payment authorization only and `Authorize and Capture` for doing instant capture.
* Set the Merchant Country as per the origin.
* Enable the log tracker to trace the `Sezzle` checkout process.
* Set `Payment from Applicable Countries` to `Specific Countries`.
* Set `Payment from Specific Countries` to `United States` or `Canada`.
* Set `Add Widget Script in PDP` to `Yes` for adding widget script in the Product Display Page which will help in enabling `Sezzle Widget` Modal in PDP.
* Set `Add Widget Script in Cart Page` to `Yes` for adding widget script in the Cart Page which will help in enabling `Sezzle Widget` Modal in Cart Page.
* Save the configuration and clear the cache.

### Your store is now ready to accept payments through Sezzle.

## Frontend Functonality

* If you have correctly set up `Sezzle`, you will see `Sezzle` as a payment method in the checkout page.
* Select `Sezzle` and move forward.
* Once you click `Place Order`, you will be redirected to `Sezzle Checkout` to complete the checkout and eventually in `Magento` too.

## Order Verification in Magento Admin

* Login to `Magento` admin and navigate to `Sales > Orders`.
* Proceed into the corresponding order.
* If `Total Paid` is equals to `Grand Total`, payment is successfully captured by `Sezzle`.
* If `Total Paid` is not equals to `Grand Total`, payment is authorized but yet not captured.

## Order Verification in Sezzle Merchant Dashboard

* Login to `Sezzle Merchant Dashboard` and navigate to `Orders`.
* Proceed into the corresponding order.
* Status as `Approved` means payment is successfully captured by `Sezzle`.
* Status as `Authorized`, uncaptured means payment is authorized but yet not captured.

