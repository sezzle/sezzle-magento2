## Sezzle Payment Gateway extension for Magento 2

This extension allows you to use Sezzle as payment gateway in your Magento 2 store.

### Installation steps
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

### Payment Setup
1. Make sure you have the merchant ID and the API Keys from the Sezzle Merchant Dashboard.
2. Navigate to `Stores/Configuration/Sales/Payment Methods/Sezzle Pay` in your Magento admin.
3. Set the base URL to `https://gateway.sezzle.com/v1`.
4. Set the Merchant ID, Public Key and Private Key.