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

You can now directly navigate from the Configuration Page to get signed up for Sezzle Pay. To do so, you need to click on `Register for Sezzle Pay` which will redirect you to the Sezzle Dashboard. If you have the details already, you can simply click on ` I've already setup Sezzle Pay, I want to edit my settings` to move ahead.

## Payment Setup
1. Make sure you have the merchant ID and the API Keys from the Sezzle Merchant Dashboard.
2. Navigate to `Stores/Configuration/Sales/Payment Methods/Sezzle Pay/Payment Settings` in your Magento admin.
3. Set the Payment Mode to `Live` for LIVE and set it as `Sandbox` for SANDBOX.
4. Set the Merchant ID, Public Key and Private Key.
5. Set the Merchant Country as per the origin.
6. Enable the log tracker to trace the Sezzle checkout process.
7. Save the configuration and clear the cache.

## Product Widget Setup
1. Navigate to `Stores/Configuration/Sales/Payment Methods/Sezzle Pay/Widget Settings/Product Page` in your Magento admin.
2. Provide the below necessary information so that Sezzle widget comes up in the product page in frontend.
   - Price Block Selector : XPath of the price element.
   - Product page:render to element path : Location where to render the widget.
   - Show in all countries : Provide as per your requirement.
   - Alignment : Position of the widget.
   - Theme : Widget theme that depends on your site’s background.
   - Width type : Text width of the widget.
   - Image url : If you want to have different logo, paste the url here.
   - Hide classes : Classes to be hidden when sezzle widget is in place.
3. Save the configuration and clear the cache.

## Cart Widget Setup
1. Navigate to `Stores/Configuration/Sales/Payment Methods/Sezzle Pay/Widget Settings/Cart Page` in your Magento admin.
2. Provide the below necessary information so that Sezzle widget comes up in the product page in frontend.
   - Price Block Selector : XPath of the price element.
   - Cart page:render to element path : Location where to render the widget.
   - Show in all countries : Provide as per your requirement.
   - Alignment : Position of the widget.
   - Theme : Widget theme that depends on your site’s background.
   - Width type : Text width of the widget.
   - Image url : If you want to have different logo, paste the url here.
   - Hide classes : Classes to be hidden when sezzle widget is in place.
3. Save the configuration and clear the cache.
```
