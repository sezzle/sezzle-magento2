/**
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */
define(
    [
        'jquery',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/model/resource-url-manager',
        'mage/storage',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/url-builder',
        'Magento_Checkout/js/model/error-processor',
        'Magento_CheckoutAgreements/js/model/agreements-assigner'
    ],
    function (
        $,
        customer,
        resourceUrlManager,
        storage,
        Component,
        additionalValidators,
        quote,
        fullScreenLoader,
        urlBuilder,
        errorProcessor,
        agreementsAssigner) {
        'use strict';

        var serviceUrl,
            payload = {};
        return Component.extend({
            defaults: {
                template: 'Sezzle_Sezzlepay/payment/sezzle'
            },

            /**
             * Check is customer uuid is available
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
             * @returns string
             */
            getSubmitButtonName: function () {
                return this.hasCustomerUUID() ? "Place Order" : "Continue to Sezzle";
            },

            /**
             * Get loader message
             * @returns string
             */
            getLoaderMsg: function () {
                return this.hasCustomerUUID() ? "Placing your order..." : "Redirecting you to Sezzle Checkout...";
            },

            /**
             * Get Sezzle Image src
             * @returns string
             */
            getSezzleImgSrc: function () {
                return 'https://d34uoa9py2cgca.cloudfront.net/branding/sezzle-logos/sezzle-pay-over-time-no-interest@2x.png';
            },

            /**
             * Get Grand Total of the current cart
             * @returns {*}
             */
            getGrandTotal: function () {

                var total = quote.getCalculatedTotal();
                var format = window.checkoutConfig.priceFormat.pattern;

                storage.get(resourceUrlManager.getUrlForCartTotals(quote), false)
                    .done(
                        function (response) {

                            var amount = response.base_grand_total;
                            var installmentFee = response.base_grand_total / 4;
                            var installmentFeeLast = amount - installmentFee.toFixed(window.checkoutConfig.priceFormat.precision) * 3;

                            $(".sezzle-grand-total").text('Total : '+format.replace(/%s/g, amount.toFixed(window.checkoutConfig.priceFormat.precision)));
                            $(".sezzle-installment-amount").text(format.replace(/%s/g, installmentFee.toFixed(window.checkoutConfig.priceFormat.precision)));
                            $(".sezzle-installment-amount.final").text(format.replace(/%s/g, installmentFeeLast.toFixed(window.checkoutConfig.priceFormat.precision)));

                            return format.replace(/%s/g, amount);
                        }
                    )
                    .fail(
                        function (response) {
                            //do your error handling

                            return 'Error';
                        }
                    );
            },

            /**
             * Handle redirection
             */
            handleRedirectAction: function () {
                var self = this,
                    paymentData = this.getData();

                this.isPlaceOrderActionAllowed(false);
                agreementsAssigner(paymentData);
                payload = {
                    cartId: quote.getQuoteId(),
                    billingAddress: quote.billingAddress(),
                    paymentMethod: paymentData,
                    createSezzleCheckout : true
                };
                if (!customer.isLoggedIn()) {
                    serviceUrl = urlBuilder.createUrl('/sezzle/guest-carts/:cartId/create-checkout', {
                        cartId: quote.getQuoteId()
                    });
                    payload.email = quote.guestEmail;
                } else {
                    serviceUrl = urlBuilder.createUrl('/sezzle/carts/mine/create-checkout', {});
                }

                fullScreenLoader.startLoader();
                return storage.post(
                    serviceUrl, JSON.stringify(payload)
                ).success(function (response) {
                    var jsonResponse = $.parseJSON(response);
                    $.mage.redirect(jsonResponse.checkout_url);
                }).fail(function (response) {
                    fullScreenLoader.stopLoader();
                    errorProcessor.process(response, this.messageContainer);
                }).always(function () {
                    self.isPlaceOrderActionAllowed(true);
                });
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
