/**
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */
define([
    "uiComponent",
    "jquery",
    "Magento_Customer/js/model/customer",
    "Sezzle_Sezzlepay/js/express-checkout/express-checkout-wrapper",
    "Sezzle_Sezzlepay/js/action/create-sezzle-express-checkout",
    "Sezzle_Sezzlepay/js/action/create-sezzle-customer-order",
    "Magento_Checkout/js/action/redirect-on-success",
], function (
    Component,
    $,
    customer,
    ExpressCheckoutWrapper,
    createSezzleExpressCheckoutAction,
    createSezzleCustomerOrder,
    redirectOnSuccessAction
) {
    "use strict";

    return Component.extend(ExpressCheckoutWrapper).extend({
        defaults: {
            template: "Sezzle_Sezzlepay/cart/express-checkout",
        },

        /**
         * Get Place Order button name
         *
         * @returns string
         */
        getSubmitButtonName: function () {
            return "Continue to Sezzle";
        },

        /**
         * Get loader message
         *
         * @returns string
         */
        getLoaderMsg: function () {
            return "Redirecting you to Sezzle Checkout...";
        },

        /**
         * Get Sezzle Image src
         *
         * @returns string
         */
        getSezzleImgSrc: function () {
            return window.checkoutConfig.payment.sezzlepay.img_src;
        },

        /**
         * Get payment method data
         *
         * @returns {Object}
         */
        getData: function () {
            return {
                method: "sezzlepay",
                additional_data: null,
                po_number: null,
            };
        },

        /**
         * Place Order click event
         */
        continueToSezzle: function (data, event) {
            if (event) {
                event.preventDefault();
            }
        },
    });
});
