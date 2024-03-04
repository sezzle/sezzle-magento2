<div align="center">
    <a href="https://sezzle.com">
        <img src="https://media.sezzle.com/branding/2.0/Sezzle_Logo_FullColor.svg" width="300px" alt="Sezzle" />
    </a>
</div>

# Sezzle Magento 2 Extension Changelog

## Version 7.0.16

_Mon 4 Mar 2024_

### Supported Editions & Versions

Tested and verified in clean installations of Magento 2:

- Magento Open Source Edition (CE) version 2.3 and later.
- Magento Commerce On Prem Edition (EE) version 2.3 and later.
- Magento Commerce Cloud Edition (ECE) version 2.3 and later.

### Highlights

- FIX: Sezzle logs are not getting sent Sezzle via cron.
- Sending sezzle logs enabled by default.
- Sezzle logs cron set to run every 2 hour daily.

## Version 7.0.15

_Thu 15 Feb 2024_

### Supported Editions & Versions

Tested and verified in clean installations of Magento 2:

- Magento Open Source Edition (CE) version 2.3 and later.
- Magento Commerce On Prem Edition (EE) version 2.3 and later.
- Magento Commerce Cloud Edition (ECE) version 2.3 and later.

### Highlights

- Set public key while initializing checkout SDK so that SDK logs can be sent.
- FIX: Safari blocking Sezzle checkout popup.

## Version 7.0.14

_Wed 29 Nov 2023_

### Supported Editions & Versions

Tested and verified in clean installations of Magento 2:

- Magento Open Source Edition (CE) version 2.3 and later.
- Magento Commerce On Prem Edition (EE) version 2.3 and later.
- Magento Commerce Cloud Edition (ECE) version 2.3 and later.

### Highlights

- FIX: Setting correct type while sending config.

## Version 7.0.13

_Tue 29 Aug 2023_

### Supported Editions & Versions

Tested and verified in clean installations of Magento 2:

- Magento Open Source Edition (CE) version 2.3 and later.
- Magento Commerce On Prem Edition (EE) version 2.3 and later.
- Magento Commerce Cloud Edition (ECE) version 2.3 and later.

### Highlights

- Payment method availability workaround through observer.

## Version 7.0.12

_Wed 23 Aug 2023_

### Supported Editions & Versions

Tested and verified in clean installations of Magento 2:

- Magento Open Source Edition (CE) version 2.3 and later.
- Magento Commerce On Prem Edition (EE) version 2.3 and later.
- Magento Commerce Cloud Edition (ECE) version 2.3 and later.

### Highlights

- FIX: Error completing in-context checkout.

## Version 7.0.11

_Mon 10 Jul 2023_

### Supported Editions & Versions

Tested and verified in clean installations of Magento 2:

- Magento Open Source Edition (CE) version 2.3 and later.
- Magento Commerce On Prem Edition (EE) version 2.3 and later.
- Magento Commerce Cloud Edition (ECE) version 2.3 and later.

### Highlights

- FIX: Unable to create Sezzle checkout through graphql.

## Version 7.0.10

_Wed 21 Jun 2023_

### Supported Editions & Versions

Tested and verified in clean installations of Magento 2:

- Magento Open Source Edition (CE) version 2.3 and later.
- Magento Commerce On Prem Edition (EE) version 2.3 and later.
- Magento Commerce Cloud Edition (ECE) version 2.3 and later.

### Highlights

- `success` and `checkout_url` fields in `CreateSezzleCheckout` and `CreateSezzleCustomerOrder`
   graphql response are made non mandatory.

## Version 7.0.9

_Thu 04 May 2023_

### Supported Editions & Versions

Tested and verified in clean installations of Magento 2:

- Magento Open Source Edition (CE) version 2.3 and later.
- Magento Commerce On Prem Edition (EE) version 2.3 and later.
- Magento Commerce Cloud Edition (ECE) version 2.3 and later.

### Highlights

- Removed usage of Zend_Locale_Math and optimized the cents conversion logic.
- Remove usage of Zend_Validate_Exception.

## Version 7.0.8

_Mon 30 Jan 2023_

### Supported Editions & Versions

Tested and verified in clean installations of Magento 2:

- Magento Open Source Edition (CE) version 2.3 and later.
- Magento Commerce On Prem Edition (EE) version 2.3 and later.
- Magento Commerce Cloud Edition (ECE) version 2.3 and later.

### Highlights

- Auto save of merchant UUID during Sezzle admin config save.
- FIX: Undefined index while saving config in scope other than default.

## Version 7.0.8

_Wed 21 Dec 2022_

### Supported Editions & Versions

Tested and verified in clean installations of Magento 2:

- Magento Open Source Edition (CE) version 2.3 and later.
- Magento Commerce On Prem Edition (EE) version 2.3 and later.
- Magento Commerce Cloud Edition (ECE) version 2.3 and later.

### Highlights

- Sending use to unauth URL if authentication is unsuccessful.

## Version 7.0.7

_Fri 16 Dec 2022_

### Supported Editions & Versions

Tested and verified in clean installations of Magento 2:

- Magento Open Source Edition (CE) version 2.3 and later.
- Magento Commerce On Prem Edition (EE) version 2.3 and later.
- Magento Commerce Cloud Edition (ECE) version 2.3 and later.

### Highlights

- FIX: Undefined array key "sezzlepay" while saving non-sezzle configuration in admin.

## Version 7.0.6

_Mon 12 Dec 2022_

### Supported Editions & Versions

Tested and verified in clean installations of Magento 2:

- Magento Open Source Edition (CE) version 2.3 and later.
- Magento Commerce On Prem Edition (EE) version 2.3 and later.
- Magento Commerce Cloud Edition (ECE) version 2.3 and later.

### Highlights

- Sending Sezzle configuration data to Sezzle.
- FIX: Setting reauthorization approved status properly.

## Version 7.0.5

_Mon 31 Oct 2022_

### Supported Editions & Versions

Tested and verified in clean installations of Magento 2:

- Magento Open Source Edition (CE) version 2.3 and later.
- Magento Commerce On Prem Edition (EE) version 2.3 and later.
- Magento Commerce Cloud Edition (ECE) version 2.3 and later.

### Highlights

- Displaying Sezzle logo in checkout page as per locale.

## Version 7.0.4

_Thr 20 Oct 2022_

### Supported Editions & Versions

Tested and verified in clean installations of Magento 2:

- Magento Open Source Edition (CE) version 2.3 and later.
- Magento Commerce On Prem Edition (EE) version 2.3 and later.
- Magento Commerce Cloud Edition (ECE) version 2.3 and later.

### Highlights

- Serving the checkout installment widget from Sezzle SDK CDN.
- Failure while sending logs to Sezzle.

## Version 7.0.3

_Mon 17 Oct 2022_

### Supported Editions & Versions

Tested and verified in clean installations of Magento 2:

- Magento Open Source Edition (CE) version 2.3 and later.
- Magento Commerce On Prem Edition (EE) version 2.3 and later.
- Magento Commerce Cloud Edition (ECE) version 2.3 and later.

### Highlights

- FIX: Unable to create checkout when there is only virtual product in cart.

## Version 7.0.2

_Wed 28 Sep 2022_

### Supported Editions & Versions

Tested and verified in clean installations of Magento 2:

- Magento Open Source Edition (CE) version 2.3 and later.
- Magento Commerce On Prem Edition (EE) version 2.3 and later.
- Magento Commerce Cloud Edition (ECE) version 2.3 and later.

### Highlights

- FIX: Unable to create checkout in certain scenarios.

## Version 7.0.1

_Fri 23 Sep 2022_

### Supported Editions & Versions

Tested and verified in clean installations of Magento 2:

- Magento Open Source Edition (CE) version 2.3 and later.
- Magento Commerce On Prem Edition (EE) version 2.3 and later.
- Magento Commerce Cloud Edition (ECE) version 2.3 and later.

### Highlights

- FIX: Sending platform and plugin details(name and version) to Sezzle for tracking/debugging purpose.
- FIX: PopUp checkout window not closing on order completion at Sezzle.

## Version 7.0.0

_Tue 16 Aug 2022_

### Supported Editions & Versions

Tested and verified in clean installations of Magento 2:

- Magento Open Source Edition (CE) version 2.3 and later.
- Magento Commerce On Prem Edition (EE) version 2.3 and later.
- Magento Commerce Cloud Edition (ECE) version 2.3 and later.

### Highlights

- Leveraging Magento payment provider gateway mechanism.

## Version 6.0.6

_Wed 20 July 2022_

### Supported Editions & Versions

Tested and verified in clean installations of Magento 2:

- Magento Open Source Edition (CE) version 2.3 and later.
- Magento Commerce On Prem Edition (EE) version 2.3 and later.
- Magento Commerce Cloud Edition (ECE) version 2.3 and later.

### Highlights

- FIX: Other module’s implementation does not appear in order view page.

## Version 6.0.5

_Tue 12 July 2022_

### Supported Editions & Versions

Tested and verified in clean installations of Magento 2:

- Magento Open Source Edition (CE) version 2.3 and later.
- Magento Commerce On Prem Edition (EE) version 2.3 and later.
- Magento Commerce Cloud Edition (ECE) version 2.3 and later.

### Highlights

- FIX: Missing reference to installment template in OSC layout.

## Version 6.0.4

_Tue 12 July 2022_

### Supported Editions & Versions

Tested and verified in clean installations of Magento 2:

- Magento Open Source Edition (CE) version 2.3 and later.
- Magento Commerce On Prem Edition (EE) version 2.3 and later.
- Magento Commerce Cloud Edition (ECE) version 2.3 and later.

### Highlights

- FIX: Sezzle showing up inconsistently in Amasty's OSC when Amasty's JS bundling is enabled.
- FIX: Installment Plan not showing up in Amasty's OSC.

## Version 6.0.3

_Wed 06 July 2022_

### Supported Editions & Versions

Tested and verified in clean installations of Magento 2:

- Magento Open Source Edition (CE) version 2.3 and later.
- Magento Commerce On Prem Edition (EE) version 2.3 and later.
- Magento Commerce Cloud Edition (ECE) version 2.3 and later.

### Highlights

- FIX: Admin order view plugin blocking other plugins to execute.

## Version 6.0.2

_Mon 09 May 2022_

### Supported Editions & Versions

Tested and verified in clean installations of Magento 2:

- Magento Open Source Edition (CE) version 2.3 and later.
- Magento Commerce On Prem Edition (EE) version 2.3 and later.
- Magento Commerce Cloud Edition (ECE) version 2.3 and later.

### Highlights

- FIX: Items are not cleared from mini cart after order place.

## Version 6.0.1

_Tue 05 Apr 2022_

### Supported Editions & Versions

Tested and verified in clean installations of Magento 2:

- Magento Open Source Edition (CE) version 2.3 and later.
- Magento Commerce On Prem Edition (EE) version 2.3 and later.
- Magento Commerce Cloud Edition (ECE) version 2.3 and later.

### Highlights

- Sending platform and plugin details(name and version) to Sezzle for tracking/debugging purpose.

## Version 6.0.0

_Tue 08 Mar 2022_

### Supported Editions & Versions

Tested and verified in clean installations of Magento 2:

- Magento Open Source Edition (CE) version 2.3 and later.
- Magento Commerce On Prem Edition (EE) version 2.3 and later.
- Magento Commerce Cloud Edition (ECE) version 2.3 and later.

### Highlights

- Support for Sezzle India

## Version 5.5.10

_Fri 04 Mar 2022_

### Supported Editions & Versions

Tested and verified in clean installations of Magento 2:

- Magento Open Source Edition (CE) version 2.3 and later.
- Magento Commerce On Prem Edition (EE) version 2.3 and later.
- Magento Commerce Cloud Edition (ECE) version 2.3 and later.

### Highlights

- FIX: The requested qty is not available error while checking out with Sezzle for configurable child product's quantity equals to 1.

## Version 5.5.9

_Tue 01 Mar 2022_

### Supported Editions & Versions

Tested and verified in clean installations of Magento 2:

- Magento Open Source Edition (CE) version 2.0 and later.
- Magento Commerce On Prem Edition (EE) version 2.0 and later.
- Magento Commerce Cloud Edition (ECE) version 2.0 and later.

### Highlights

- FIX: Compilation error for < PHP 7.4.

## Version 5.5.8

_Tue 25 Jan 2022_

### Supported Editions & Versions

Tested and verified in clean installations of Magento 2:

- Magento Open Source Edition (CE) version 2.0 and later.
- Magento Commerce On Prem Edition (EE) version 2.0 and later.
- Magento Commerce Cloud Edition (ECE) version 2.0 and later.

### Highlights

- Added a button in Sezzle configuration section for creating widget request in case of any issue related to widget.
- Migrated install/upgrade scripts to declarative schema.

## Version 5.5.7

_Mon 20 Dec 2021_

### Supported Editions & Versions

Tested and verified in clean installations of Magento 2:

- Magento Open Source Edition (CE) version 2.0 and later.
- Magento Commerce On Prem Edition (EE) version 2.0 and later.
- Magento Commerce Cloud Edition (ECE) version 2.0 and later.

### Highlights

- Fetching of default gateway region compatibility for PHP <7.3.0

## Version 5.5.6

_Wed 24 Nov 2021_

### Supported Editions & Versions

Tested and verified in clean installations of Magento 2:

- Magento Open Source Edition (CE) version 2.0 and later.
- Magento Commerce On Prem Edition (EE) version 2.0 and later.
- Magento Commerce Cloud Edition (ECE) version 2.0 and later.

### Highlights

- Adding Italian language support to the installment widget.
- Adding Customer Session Id, Magento and Sezzle version to the log records.

## Version 5.5.5

_Fri 01 Oct 2021_

### Supported Editions & Versions

Tested and verified in clean installations of Magento 2:

- Magento Open Source Edition (CE) version 2.0 and later.
- Magento Commerce On Prem Edition (EE) version 2.0 and later.
- Magento Commerce Cloud Edition (ECE) version 2.0 and later.

### Highlights

- FIX: Sezzle Checkout and Logging.

## Version 5.5.4

_Mon 20 Sep 2021_

### Supported Editions & Versions

Tested and verified in clean installations of Magento 2:

- Magento Open Source Edition (CE) version 2.0 and later.
- Magento Commerce On Prem Edition (EE) version 2.0 and later.
- Magento Commerce Cloud Edition (ECE) version 2.0 and later.

### Highlights

- Removing Merchant Country field from settings.

## Version 5.5.3

_Tue 31 Aug 2021_

### Supported Editions & Versions

Tested and verified in clean installations of Magento 2:

- Magento Open Source Edition (CE) version 2.0 and later.
- Magento Commerce On Prem Edition (EE) version 2.0 and later.
- Magento Commerce Cloud Edition (ECE) version 2.0 and later.

### Highlights

- FIX: The requested qty is not available even though qty is available.

## Version 5.5.2

_Wed 11 Aug 2021_

### Supported Editions & Versions

Tested and verified in clean installations of Magento 2:

- Magento Open Source Edition (CE) version 2.0 and later.
- Magento Commerce On Prem Edition (EE) version 2.0 and later.
- Magento Commerce Cloud Edition (ECE) version 2.0 and later.

### Highlights

- Gateway V1 URL fix.

## Version 5.5.1

_Wed 2 Jun 2021_

### Supported Editions & Versions

Tested and verified in clean installations of Magento 2:

- Magento Open Source Edition (CE) version 2.0 and later.
- Magento Commerce On Prem Edition (EE) version 2.0 and later.
- Magento Commerce Cloud Edition (ECE) version 2.0 and later.

### Highlights

- Allow store language settings to determine Sezzle-Checkout language.
- Default widget configs.


## Version 5.5.0

_Wed 21 April 2021_

### Supported Editions & Versions

Tested and verified in clean installations of Magento 2:

- Magento Open Source Edition (CE) version 2.0 and later.
- Magento Commerce On Prem Edition (EE) version 2.0 and later.
- Magento Commerce Cloud Edition (ECE) version 2.0 and later.

### Highlights

- EU Support.


_Thr 1 April 2021_

### Supported Editions & Versions

Tested and verified in clean installations of Magento 2:

- Magento Open Source Edition (CE) version 2.0 and later.
- Magento Commerce On Prem Edition (EE) version 2.0 and later.
- Magento Commerce Cloud Edition (ECE) version 2.0 and later.

### Highlights

- Create checkout service improvement.
- GuestCartManagement used for placing guest order.

## Version 5.3.2

_Wed 24 Mar 2021_

### Supported Editions & Versions

Tested and verified in clean installations of Magento 2:

- Magento Open Source Edition (CE) version 2.0 and later.
- Magento Commerce On Prem Edition (EE) version 2.0 and later.
- Magento Commerce Cloud Edition (ECE) version 2.0 and later.

### Highlights

- FIX : Terms & Condition not checked while checking out with Sezzle.

## Version 5.3.1

_Mon 12 Mar 2021_

### Supported Editions & Versions

Tested and verified in clean installations of Magento 2:

- Magento Open Source Edition (CE) version 2.0 and later.
- Magento Commerce On Prem Edition (EE) version 2.0 and later.
- Magento Commerce Cloud Edition (ECE) version 2.0 and later.

### Highlights

- FIX : Installment Widget Price Path issue in PDP.

## Version 5.3.0

_Mon 25 Jan 2021_

### Supported Editions & Versions

Tested and verified in clean installations of Magento 2:

- Magento Open Source Edition (CE) version 2.0 and later.
- Magento Commerce On Prem Edition (EE) version 2.0 and later.
- Magento Commerce Cloud Edition (ECE) version 2.0 and later.

### Highlights

- Installment Widget in Checkout Page.

## Version 5.2.1

_Tue 10 Dec 2020_

### Supported Editions & Versions

Tested and verified in clean installations of Magento 2:

- Magento Open Source Edition (CE) version 2.0 and later.
- Magento Commerce On Prem Edition (EE) version 2.0 and later.
- Magento Commerce Cloud Edition (ECE) version 2.0 and later.

### Highlights

- Improved Quote Validation.
- Making use of the standard order placement functions. 

## Version 5.2.0

_Tue 23 Nov 2020_

### Supported Editions & Versions

Tested and verified in clean installations of Magento 2:

- Magento Open Source Edition (CE) version 2.0 and later.
- Magento Commerce On Prem Edition (EE) version 2.0 and later.
- Magento Commerce Cloud Edition (ECE) version 2.0 and later.

### Highlights

- Capability of reauthorizing expired orders.
- Preference of tokenized checkout over in-context checkout.
- FIX:Showing of Sezzle Referece ID in all orders in Storefront.

## Version 5.1.1

_Tue 10 Nov 2020_

### Supported Editions & Versions

Tested and verified in clean installations of Magento 2:

- Magento Open Source Edition (CE) version 2.0 and later.
- Magento Commerce On Prem Edition (EE) version 2.0 and later.
- Magento Commerce Cloud Edition (ECE) version 2.0 and later.

### Highlights

- FIX : Visibility of Sezzle Information section for other payment method's order in Admin Panel.

## Version 5.1.0

_Tue 5 Nov 2020_

### Supported Editions & Versions

Tested and verified in clean installations of Magento 2:

- Magento Open Source Edition (CE) version 2.0 and later.
- Magento Commerce On Prem Edition (EE) version 2.0 and later.
- Magento Commerce Cloud Edition (ECE) version 2.0 and later.

### Highlights

- Aheadworks OneStepCheckout Compatibility.
- Capability of reauthorizing expired orders.
- Preference of tokenized checkout over in-context checkout.

## Version 5.0.4

_Tue 2 Nov 2020_

### Supported Editions & Versions

Tested and verified in clean installations of Magento 2:

- Magento Open Source Edition (CE) version 2.0 and later.
- Magento Commerce On Prem Edition (EE) version 2.0 and later.
- Magento Commerce Cloud Edition (ECE) version 2.0 and later.

### Highlights

- Render the Sezzle Widget after the Order Totals section loads in Cart Page.
- Capability of reauthorizing expired orders.

## Version 5.0.3

_Tue 29 Oct 2020_

### Supported Editions & Versions

Tested and verified in clean installations of Magento 2:

- Magento Open Source Edition (CE) version 2.0 and later.
- Magento Commerce On Prem Edition (EE) version 2.0 and later.
- Magento Commerce Cloud Edition (ECE) version 2.0 and later.

### Highlights

- Round up issue fix for certain 3 decimal values.
- Failing capture, refund and release if not successful.

## Version 5.0.2

_Tue 19 Oct 2020_

### Supported Editions & Versions

Tested and verified in clean installations of Magento 2:

- Magento Open Source Edition (CE) version 2.0 and later.
- Magento Commerce On Prem Edition (EE) version 2.0 and later.
- Magento Commerce Cloud Edition (ECE) version 2.0 and later.

### Highlights

- New Sezzle Checkout Button for InContext Checkout only.

## Version 5.0.1

_Tue 13 Oct 2020_

### Supported Editions & Versions

Tested and verified in clean installations of Magento 2:

- Magento Open Source Edition (CE) version 2.0 and later.
- Magento Commerce On Prem Edition (EE) version 2.0 and later.
- Magento Commerce Cloud Edition (ECE) version 2.0 and later.

### Highlights

- Twice Checkout Creation on Auth Only Fix.

## Version 5.0.0

_Tue 9 Sep 2020_

### Supported Editions & Versions

Tested and verified in clean installations of Magento 2:

- Magento Open Source Edition (CE) version 2.0 and later.
- Magento Commerce On Prem Edition (EE) version 2.0 and later.

### Highlights

- InContext Checkout via Sezzle SDK implementation. Checkout modes are: 
    - IFrame
    - PopUp
- Default to Redirection Checkout for Mobile/Tablet Device.
- Tokenization not allowed for InContext Checkout.
- Dollar to Cent Conversion improvement in implementation.
- REST API for Creating Checkout and Place Order for Sezzle Orders.
- Redirect Controller removed.
- CSP Whitelist of Sezzle domain for IFrame Checkout.
- Auth Expiration double-layered validation.
