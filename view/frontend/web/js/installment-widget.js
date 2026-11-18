/**
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */
define([
    'Magento_Checkout/js/model/quote',
    'installmentWidgetSDK'
], function (quote) {
    'use strict';

    /**
     * The shopper's country, read from the quote. Billing is what Sezzle
     * checks eligibility against, so it wins; shipping covers the window
     * before a billing address has been entered. A virtual quote has no real
     * shipping address (its stub carries the tax default country), so it is
     * skipped there and the store's default country is used instead.
     *
     * @returns {String|undefined}
     */
    function getShopperCountry() {
        var billing = quote.billingAddress(),
            shipping = quote.isVirtual() ? null : quote.shippingAddress(),
            config = window.checkoutConfig.payment.sezzlepay || {};

        return (billing && billing.countryId) ||
            (shipping && shipping.countryId) ||
            config.storeCountry ||
            undefined;
    }

    return function () {
        var country = getShopperCountry(),
            widget = new SezzleInstallmentWidget({
                'countryCode': country,
                'platform': 'magento2'
            });

        /**
         * The widget reads countryCode only in its constructor, and a second
         * instance would start a second render loop, so a mid-checkout change of
         * country (e.g. a US shopper switching to a Canadian billing address) is
         * applied to this instance. Emptying the box makes its render loop draw
         * the schedule again for the new country (Pay in 4 only for CA).
         */
        function onAddressChange() {
            var next = getShopperCountry(),
                box,
                modal;

            if (next === country) {
                return;
            }

            country = next;
            widget.countryCode = next ? next.toUpperCase() : undefined;

            box = document.getElementById('sezzle-installment-widget-box');

            if (box) {
                box.innerHTML = '';
            }

            modal = document.getElementById('sezzle-modal-container');

            if (modal) {
                modal.remove();
            }
        }

        quote.billingAddress.subscribe(onAddressChange);
        quote.shippingAddress.subscribe(onAddressChange);
    };
});
