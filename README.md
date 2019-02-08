## Sezzle Payment Gateway extension for Magento 2

This extension allows you to use Sezzle as payment gateway in your Magento 2 store.

## Installation steps

### Composer
1. `composer require sezzle/sezzlepay`
2. `php bin/magento setup:upgrade`
3. `php bin/magento setup:di:compile`
4. `php bin/magento setup:static-content:deploy`
5. `php bin/magento cache:clean`

### Manual
1. Signup for Sezzle at `https://dashboard.sezzle.com/merchant/signup/`. Login to your dashboard and keep your API Keys page open.
2. In your Magento 2 `[ROOT]/app/code/` create folder called `Sezzle`.
3. Inside `Sezzle`, create folder called `Sezzlepay`.
4. Inside it, extract the files from this repo.
5. Open the command line.
6. Run the below command to enable Sezzle:
`php bin/magento module:enable Sezzle_Sezzlepay`
7. Run the Magento setup upgrade:
`php bin/magento setup:upgrade`
8. Run the Magento Dependencies Injection Compile:
`php bin/magento setup:di:compile`
9. Run the Magento Static Content deployment:
`php bin/magento setup:static-content:deploy`
10. Login to Magento Admin and navigate to System/Cache Management
11. Flush the cache storage by selecting Flush Cache Storage

## Payment Setup
1. Make sure you have the merchant ID and the API Keys from the Sezzle Merchant Dashboard.
2. Navigate to `Stores/Configuration/Sales/Payment Methods/Sezzle Pay` in your Magento admin.
3. Set the base URL to `https://gateway.sezzle.com`.
4. Set the Merchant ID, Public Key and Private Key.

## Troubleshooting

### What to do if I see "Invalid header line detected" error message when placing an order with Sezzle?

Reason for this error is: Our Load Balancers support both HTTP/1.1 and HTTP/2. As a result, clients that support HTTP/2 will auto upgrade. It's likely that cURL also auto upgrades to HTTP/2, transparently i.e. it sends HTTP/2 request with a HTTP/2 response, on the wire. We use magento's core Zend Framework library for curl request and it does not support HTTP/2. We have plans to switch to another library or Magento's curl in future, to fix the issue for now please follow these instructions to apply patch to add HTTP/2 support to ZF1 library.
```php
1. File path : <magento root>/vendor/magento/zendframework1/library/Zend/Http/Response.php, modify around line 185 :
From: 
        if (! preg_match('|^\d\.\d$|', $version)) {
To:
        if (! preg_match('|^\d\.\d$|', $version) && ($version != 2)) {

2. File path : <magento root>/vendor/magento/zendframework1/library/Zend/Http/Response.php, modify around line 586 :
From:
        if ($index === 0 && preg_match('#^HTTP/\d+(?:\.\d+) [1-5]\d+#', $line)) {
            // Status line; ignore
            continue;
        }
To:
        if ($index === 0 && preg_match('#^HTTP/\d+(?:\.\d+) [1-5]\d+#', $line)) {
            // Status line; ignore
            continue;
        }

        if ($index === 0 && preg_match('#^HTTP/2 200#', $line)) {
            // Status line; ignore
            continue;
        }
```