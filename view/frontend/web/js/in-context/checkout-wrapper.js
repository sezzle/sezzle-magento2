/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'jquery',
    'mage/translate',
    'Magento_Customer/js/customer-data',
    'Sezzle_Sezzlepay/js/in-context/sezzle-smart-button',
    'Magento_Checkout/js/model/quote',
    'mage/storage',
    'mage/url',
    'Magento_Customer/js/model/customer',
    'mage/cookies',
], function ($, $t, customerData, checkoutSmartButtons, quote, storage, mageUrl, customer) {
    'use strict';

    return {
        defaults: {
            paymentActionError: $t('Something went wrong with your request. Please try again later.'),
            signInMessage: $t('To check out, please sign in with your email address.')
        },

        /**
         * Render PayPal buttons using checkout.js
         */
        initSezzleSDKCheckout: function (element) {
            checkoutSmartButtons(this.prepareClientConfig(), element);
        },

        getSezzleCheckoutURL: function () {
            var url = mageUrl.build("sezzle/payment/redirect");
            var data = $("#co-shipping-form").serialize();
            if (!customer.isLoggedIn()) {
                var email = quote.guestEmail;
                data += '&email=' + email;
            }


            $.ajax({
                url: url,
                method:'post',
                showLoader: true,
                data: data,
                success: function (response) {
                    // Send this response to sezzle api
                    // This would redirect to sezzle
                    var jsonData = $.parseJSON(response);
                    if (jsonData.redirectURL) {
                        return jsonData.redirectURL;
                    } else if (typeof jsonData['message'] !== 'undefined') {
                        // globalMessageList.addErrorMessage({
                        //     'message': jsonData['message']
                        // });
                    }
                }
            });
        },

        getSDKConfig: function() {
            return {
                'mode': this.clientConfig.inContextMode
            };
        },

        getCaptureObject: function () {
            return {
                payload: {
                    capture_amount: {
                        amount_in_cents: 5000,
                        currency: "USD"
                    },
                    partial_capture: true
                }
            };
        },

        getCheckoutObject: function () {
            return {
                "amount_in_cents": 12999,
                "currency_code": "USD",
                "order_reference_id": "fgdfgffgh",
                "order_description": "Order #1800",
                "checkout_cancel_url": "https://sezzle.com/cart",
                "checkout_complete_url": "https://sezzle.com/complete",
                "customer_details":
                    {
                        "first_name": "John",
                        "last_name": "Doe",
                        "email": "john.doe@sezzle.com",
                        "phone": "5555045294"
                    },
                "billing_address": {
                    "name": "John Doe",
                    "street": "123 W Lake St",
                    "street2": "Unit 104",
                    "city": "Minneapolis",
                    "state": "MN",
                    "postal_code": "55408",
                    "country_code": "US",
                    "phone_number": "5555045294"
                },
                "shipping_address": {
                    "name": "John Doe",
                    "street": "123 W Lake St",
                    "street2": "Unit 104",
                    "city": "Minneapolis",
                    "state": "MN",
                    "postal_code": "55408",
                    "country_code": "US",
                    "phone_number": "5555045294"
                },
                "requires_shipping_info": false,
                "merchant_completes": true
            };
        },

        afterOnComplete: function () {
            var url = mageUrl.build("sezzle/payment/complete");
            return storage.post(url, data);
        },

        beforeOnComplete: function () {},

        catchOnComplete: function () {},

        afterOnClick: function () {},

        beforeOnClick: function () {
            var url = mageUrl.build("sezzle/payment/redirect");
            var data = $("#co-shipping-form").serialize();
            if (!customer.isLoggedIn()) {
                data = data.concat("&email=", quote.guestEmail);
            }
            var url = 'rest/default/V1/sezzle/mine/create-checkout'
            return storage.post(url, data);
        },

        catchOnClick: function () {},

        // /**
        //  * Validate payment method
        //  *
        //  * @param {Object} actions
        //  */
        // validate: function (actions) {
        //     this.actions = actions || this.actions;
        // },
        //
        // /**
        //  * Execute logic on Paypal button click
        //  */
        // onClick: function () {},
        //
        // /**
        //  * Before payment execute
        //  *
        //  * @param {Function} resolve
        //  * @param {Function} reject
        //  * @return {*}
        //  */
        // beforePayment: function (resolve, reject) { //eslint-disable-line no-unused-vars
        //     return $.Deferred().resolve();
        // },
        //
        // /**
        //  * After payment execute
        //  *
        //  * @param {Object} res
        //  * @param {Function} resolve
        //  * @param {Function} reject
        //  *
        //  * @return {*}
        //  */
        // afterPayment: function (res, resolve, reject) {
        //     if (res.success) {
        //         return resolve(res.token);
        //     }
        //
        //     this.addError(res['error_message']);
        //
        //     return reject(new Error(res['error_message']));
        // },
        //
        // /**
        //  * Catch payment
        //  *
        //  * @param {Error} err
        //  * @param {Function} resolve
        //  * @param {Function} reject
        //  */
        // catchPayment: function (err, resolve, reject) {
        //     this.addError(this.paymentActionError);
        //     reject(err);
        // },
        //
        // /**
        //  * Before onAuthorize execute
        //  *
        //  * @param {Function} resolve
        //  * @param {Function} reject
        //  * @param {Object} actions
        //  *
        //  * @return {jQuery.Deferred}
        //  */
        // beforeOnAuthorize: function (resolve, reject, actions) { //eslint-disable-line no-unused-vars
        //     return $.Deferred().resolve();
        // },
        //
        // /**
        //  * After onAuthorize execute
        //  *
        //  * @param {Object} res
        //  * @param {Function} resolve
        //  * @param {Function} reject
        //  * @param {Object} actions
        //  *
        //  * @return {*}
        //  */
        // afterOnAuthorize: function (res, resolve, reject, actions) {
        //     if (res.success) {
        //         resolve();
        //
        //         return actions.redirect(window, res.redirectUrl);
        //     }
        //
        //     this.addError(res['error_message']);
        //
        //     return reject(new Error(res['error_message']));
        // },
        //
        // /**
        //  * Catch payment
        //  *
        //  * @param {Error} err
        //  * @param {Function} resolve
        //  * @param {Function} reject
        //  */
        // catchOnAuthorize: function (err, resolve, reject) {
        //     this.addError(this.paymentActionError);
        //     reject(err);
        // },
        //
        // /**
        //  * Process cancel action
        //  *
        //  * @param {Object} data
        //  * @param {Object} actions
        //  */
        // onCancel: function (data, actions) {
        //     actions.redirect(window, this.clientConfig.onCancelUrl);
        // },
        //
        // /**
        //  * Process errors
        //  *
        //  * @param {Error} err
        //  */
        // onError: function (err) { //eslint-disable-line no-unused-vars
        //     // Uncaught error isn't displayed in the console
        // },
        //
        // /**
        //  * Adds error message
        //  *
        //  * @param {String} message
        //  * @param {String} [type]
        //  */
        // addError: function (message, type) {
        //     type = type || 'error';
        //     customerData.set('messages', {
        //         messages: [{
        //             type: type,
        //             text: message
        //         }],
        //         'data_id': Math.floor(Date.now() / 1000)
        //     });
        // },
        //
        /**
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
            //this.clientConfig.client = {};
            //this.clientConfig.client[this.clientConfig.environment] = this.clientConfig.merchantId;
            this.clientConfig.rendererComponent = this;
            this.clientConfig.formKey = $.mage.cookies.get('form_key');
            this.clientConfig.inContextMode = window.checkoutConfig.payment.sezzlepay.inContextMode;

            return this.clientConfig;
        }
    };
});
