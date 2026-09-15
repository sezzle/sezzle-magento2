/**
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */
define(
    [
        'jquery',
        'mage/translate',
        'Magento_Checkout/js/model/quote',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Sezzle_Sezzlepay/js/action/create-sezzle-checkout',
        'Sezzle_Sezzlepay/js/action/create-sezzle-customer-order',
        'Magento_Checkout/js/action/redirect-on-success',
    ],
    function (
        $,
        $t,
        quote,
        customer,
        Component,
        additionalValidators,
        createSezzleCheckoutAction,
        createSezzleCustomerOrder,
        redirectOnSuccessAction) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Sezzle_Sezzlepay/payment/sezzle',
                isRequestPending: false
            },

            /**
             * @returns {Component} Chainable.
             */
            initObservable: function () {
                this._super().observe(['isRequestPending']);

                return this;
            },

            /**
             * Describe why the Sezzle action cannot run, or null when it can.
             *
             * Returning a message rather than a bare false lets callers show the
             * shopper something they can act on. Refusing silently strands them on a
             * disabled button or a spinning modal with no idea what is wrong.
             *
             * @returns {String|null}
             */
            getCheckoutBlocker: function () {
                if (this.isRequestPending()) {
                    return $t('Your request is still being processed. Please wait.');
                }

                if (this.isPlaceOrderActionAllowed()) {
                    return null;
                }

                // The quote has no billing address. When the merchant requires one it is
                // still being entered; when they do not, Magento renders no form at all
                // and reuses the shipping address, so reaching here means a virtual cart
                // with nothing to fall back on.
                if (quote.isVirtual()) {
                    return $t('Please enter a billing address.');
                }

                return $t(
                    'Your billing address has not been saved. Select Update below the billing '
                    + 'address form to save it.'
                );
            },

            /**
             * Whether the Sezzle action may run
             *
             * @returns {Boolean}
             */
            isSezzleActionAllowed: function () {
                return this.getCheckoutBlocker() === null;
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
                var isFrench =
                    document.querySelector("html").lang?.indexOf("fr") === 0;

                if (this.hasCustomerUUID()) {
                    return isFrench ? "Passer la commande" : "Place Order";
                } else {
                    return isFrench
                        ? "Continuez à Sezzle"
                        : "Continue to Sezzle";
                }
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
                return window.checkoutConfig.payment.sezzlepay.img_src;
            },

            /**
             *
             * Handle redirection
             */
            handleRedirectAction: function () {
                var self = this;

                self.isPlaceOrderActionAllowed(false);
                self.isRequestPending(true);

                // created order by customer UUID if customer is tokenized
                if (customer.isLoggedIn() && this.hasCustomerUUID()) {
                    this.getCreateSezzleCustomerOrderDeferredObject()
                        .done(
                            function (response) {
                                var jsonResponse = $.parseJSON(response);
                                if (jsonResponse.checkout_url) {
                                    $.mage.redirect(jsonResponse.checkout_url); // if tokenization fails, we create checkout and return the checkout URL
                                    return;
                                }

                                redirectOnSuccessAction.execute(); // successful customer order
                            }
                        ).always(
                        function () {
                            self.isPlaceOrderActionAllowed(true);
                            self.isRequestPending(false);
                        }
                    );
                    return;
                }

                // creates standard checkout
                this.getCreateSezzleCheckoutDeferredObject()
                    .done(
                        function (response) {
                            var jsonResponse = $.parseJSON(response);
                            $.mage.redirect(jsonResponse.checkout_url);
                        }
                    ).always(
                    function () {
                        self.isPlaceOrderActionAllowed(true);
                        self.isRequestPending(false);
                    }
                );
            },

            /**
             * Creates Sezzle order by customer UUID
             *
             * @return {*}
             */
            getCreateSezzleCustomerOrderDeferredObject: function () {
                return $.when(
                    createSezzleCustomerOrder(this.getData(), this.messageContainer)
                );
            },

            /**
             * Creates Sezzle Checkout
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

                var blocker = this.getCheckoutBlocker();

                if (blocker) {
                    this.messageContainer.addErrorMessage({message: blocker});

                    return;
                }

                if (this.validate() && additionalValidators.validate()) {
                    this.handleRedirectAction();
                }
            }
        });
    }
);
