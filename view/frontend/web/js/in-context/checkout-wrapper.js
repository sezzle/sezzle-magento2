/**
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */
define([
    'jquery',
    'mage/translate',
    'Sezzle_Sezzlepay/js/in-context/sezzle-smart-button',
    'Magento_Checkout/js/model/quote',
    'mage/storage',
    'Magento_Customer/js/model/customer',
    'Magento_Checkout/js/action/redirect-on-success',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Checkout/js/model/url-builder',
    'Magento_Checkout/js/model/error-processor',
    'Magento_Checkout/js/model/payment/additional-validators',
    'Sezzle_Sezzlepay/js/action/create-sezzle-checkout',
], function (
    $,
    $t,
    checkoutSmartButtons,
    quote,
    storage,
    customer,
    redirectOnSuccessAction,
    fullScreenLoader,
    urlBuilder,
    errorProcessor,
    additionalValidators,
    createSezzleCheckoutAction) {
    'use strict';

    var serviceUrl;

    return {
        defaults: {
            paymentActionError: $t('Something went wrong with your request. Please try again later.'),
            paymentCancelError: $t('Payment has been cancelled.'),
            paymentFailureError: $t('Payment has been failed. Verify and try again.'),
            signInMessage: $t('To check out, please sign in with your email address.')
        },

        /**
         * Render Sezzle button using checkout.js
         */
        initSezzleSDKCheckout: function () {
            checkoutSmartButtons(this.prepareClientConfig());
        },

        /**
         * Get SDK Config
         *
         * @returns {Object}
         */
        getSDKConfig: function () {
            return {
                'publicKey': this.clientConfig.publicKey,
                'mode': this.clientConfig.inContextMode,
                'apiMode': this.clientConfig.inContextTransactionMode,
                'apiVersion': this.clientConfig.inContextApiVersion
            };
        },

        /**
         * After Checkout Complete Action
         */
        afterOnComplete: function () {
            fullScreenLoader.startLoader();
            if (!customer.isLoggedIn()) {
                serviceUrl = urlBuilder.createUrl('/sezzle/guest-carts/:cartId/order', {
                    cartId: quote.getQuoteId()
                });
            } else {
                serviceUrl = urlBuilder.createUrl('/sezzle/carts/mine/order', {});
            }

            return storage.put(
                serviceUrl
            ).done(
                function () {
                    redirectOnSuccessAction.execute();
                }
            ).fail(
                function (response) {
                    errorProcessor.process(response, this.messageContainer);
                    fullScreenLoader.stopLoader(true);
                }
            );
        },

        /**
         * Before Checkout Complete Action
         */
        beforeOnComplete: function () {
        },

        /**
         * Handle Checkout Complete Exception
         */
        catchOnComplete: function () {
        },

        /**
         * After Checkout Cancel Action
         */
        afterOnCancel: function () {
        },

        /**
         * Before Checkout Cancel Action
         */
        beforeOnCancel: function () {
        },

        /**
         * Handle Checkout Cancel Exception
         */
        catchOnCancel: function () {
            errorProcessor.process(
                {
                    responseText: JSON.stringify({message:this.paymentCancelError})
                },
                this.messageContainer
            );
            fullScreenLoader.stopLoader();
        },

        /**
         * After Checkout Failure Action
         */
        afterOnFailure: function () {
        },

        /**
         * Before Checkout Failure Action
         */
        beforeOnFailure: function () {
        },

        /**
         * Handle Checkout Failure Exception
         */
        catchOnFailure: function () {
            errorProcessor.process(
                {
                    responseText: JSON.stringify({message:this.paymentFailureError})
                },
                this.messageContainer
            );
            fullScreenLoader.stopLoader();
        },

        /**
         * After Sezzle Button onClick Action
         */
        afterOnClick: function () {
        },

        /**
         * Return Sezzle Payment Method object
         */
        getSezzlePayment: function () {
            return {
                'method': 'sezzlepay',
                'additional_data': null,
                'po_number': null
            };
        },

        /**
         * Validate checkout
         *
         * @return {*}
         */
        validateCheckout: function () {
            if (this.clientConfig.isAheadworksCheckoutEnabled) {
                return this._beforeAction();
            }

            if (additionalValidators.validate() && this.isPlaceOrderActionAllowed() === true) {
                return $.Deferred().resolve();
            }
            errorProcessor.process({
                responseText: JSON.stringify({message:"Unable to process you request."})
            }, this.messageContainer);
            return $.Deferred().reject();
        },

        /**
         * Before Sezzle Button onClick Action
         *
         * @returns {Promise}
         */
        beforeOnClick: function () {
            return $.when(
                createSezzleCheckoutAction(this.getData(), this.messageContainer)
            );
        },

        /**
         * Handle Sezzle Button onClick Exception
         */
        catchOnClick: function () {
        },

        /**
         * Get Button ID
         *
         * @returns {String}
         */
        getButtonId: function () {
            return this.inContextId;
        },

        /**
         * Populate client config with all required data
         *
         * @return {Object}
         */
        prepareClientConfig: function () {
            this.clientConfig = {};
            this.clientConfig.rendererComponent = this;
            this.clientConfig.sezzleButtonContainerElementID = "sezzle-smart-button-container";
            this.clientConfig.formKey = $.mage.cookies.get('form_key');
            this.clientConfig.publicKey = window.checkoutConfig.payment.sezzlepay.publicKey;
            this.clientConfig.inContextMode = window.checkoutConfig.payment.sezzlepay.inContextMode;
            this.clientConfig.inContextTransactionMode = window.checkoutConfig.payment.sezzlepay.inContextTransactionMode;
            this.clientConfig.inContextApiVersion = window.checkoutConfig.payment.sezzlepay.inContextApiVersion;
            this.clientConfig.isAheadworksCheckoutEnabled = window.checkoutConfig.payment.sezzlepay.isAheadworksCheckoutEnabled;

            return this.clientConfig;
        }
    };
});
