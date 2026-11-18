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
     * Billing country, else shipping (non-virtual quotes only), else the store default.
     *
     * @returns {String|undefined}
     */
    function getShopperCountry() {
        var billing = quote.billingAddress(),
            shipping = quote.isVirtual() ? null : quote.shippingAddress(),
            config = window.checkoutConfig.payment.sezzlepay || {},
            country = (billing && billing.countryId) ||
                (shipping && shipping.countryId) ||
                config.storeCountry;

        return country ? country.toUpperCase() : undefined;
    }

    return function () {
        var country = getShopperCountry(),
            widget = new SezzleInstallmentWidget({
                'countryCode': country,
                'platform': 'magento2'
            });

        /**
         * Re-renders the widget when the shopper's country changes.
         *
         * Relies on SDK internals, not its public API: the countryCode property,
         * the render loop redrawing an empty #sezzle-installment-widget-box, and
         * the #sezzle-modal-container id. Replace with a public SDK method once
         * one exists.
         */
        function onAddressChange() {
            var next = getShopperCountry(),
                box,
                modal;

            if (next === country) {
                return;
            }

            country = next;
            widget.countryCode = next;

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
