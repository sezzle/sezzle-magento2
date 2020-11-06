<div align="center">
    <a href="https://sezzle.com">
        <img src="https://media.sezzle.com/branding/2.0/Sezzle_Logo_FullColor.svg" width="300px" alt="Sezzle" />
    </a>
</div>

# Sezzle Magento 2 Extension Changelog

## Version 5.2.0

_Tue 10 Nov 2020_

### Supported Editions & Versions

Tested and verified in clean installations of Magento 2:

- Magento Open Source Edition (CE) version 2.0 and later.
- Magento Commerce On Prem Edition (EE) version 2.0 and later.
- Magento Commerce Cloud Edition (ECE) version 2.0 and later.

### Highlights

- Capability of reauthorizing expired orders.
- Preference of tokenized checkout over in-context checkout.

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
