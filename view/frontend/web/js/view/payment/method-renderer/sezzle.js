/**
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */
define(
    [
        'jquery',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Sezzle_Sezzlepay/js/action/create-sezzle-checkout',
    ],
    function (
        $,
        customer,
        Component,
        additionalValidators,
        createSezzleCheckoutAction) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Sezzle_Sezzlepay/payment/sezzle'
            },

            /**
             * Check is customer uuid is available
             *
             * @returns bool
             */
            hasCustomerUUID: function () {
                var customerCustomAttributes = customer.customerData.custom_attributes;
                return customerCustomAttributes !== undefined
                    && customerCustomAttributes.sezzle_customer_uuid !== undefined
                    && customerCustomAttributes.sezzle_customer_uuid.value;
            },

            /**
             * Get Place Order button name
             *
             * @returns string
             */
            getSubmitButtonName: function () {
                return this.hasCustomerUUID() ? "Place Order" : "Continue to Sezzle";
            },

            /**
             * Get loader message
             *
             * @returns string
             */
            getLoaderMsg: function () {
                return this.hasCustomerUUID() ? "Placing your order..." : "Redirecting you to Sezzle Checkout...";
            },

            /**
             * Get Sezzle Image src
             *
             * @returns string
             */
            getSezzleImgSrc: function () {
                return window.checkoutConfig.payment.sezzlepay.logo;
            },

            /**
             * Handle redirection
             */
            handleRedirectAction: function () {
                var self = this;

                self.isPlaceOrderActionAllowed(false);

                this.getCreateSezzleCheckoutDeferredObject()
                    .done(
                        function (response) {
                            var jsonResponse = $.parseJSON(response);
                            $.mage.redirect(jsonResponse.checkout_url);
                        }
                    ).always(
                    function () {
                        self.isPlaceOrderActionAllowed(true);
                    }
                );
            },

            /**
             * Get Create Sezzle Checkout Deferred Object
             *
             * @return {*}
             */
            getCreateSezzleCheckoutDeferredObject: function () {
                return $.when(
                    createSezzleCheckoutAction(this.getData(), this.messageContainer)
                );
            },

            /**
             * Place Order click event
             */
            continueToSezzle: function (data, event) {
                if (event) {
                    event.preventDefault();
                }

                if (this.validate()
                    && additionalValidators.validate()
                    && this.isPlaceOrderActionAllowed() === true) {
                    this.handleRedirectAction();
                }
            }
        });
    }
);
