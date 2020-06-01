/*
 * @category    Sezzle
 * @package     Sezzle_Payment
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */
define(
    [
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/model/resource-url-manager',
        'mage/storage',
        'Magento_Checkout/js/view/payment/default',
        'jquery',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/action/set-payment-information',
        'mage/url',
        'mage/translate',
        'Magento_Checkout/js/checkout-data',
        'Magento_Checkout/js/action/select-payment-method',
        'Magento_Ui/js/model/messageList',
        'Magento_Checkout/js/model/quote'
    ],
    function (customer, resourceUrlManager, storage, Component, $, additionalValidators, setPaymentInformationAction, mageUrl, $t, checkoutData, selectPaymentMethodAction, globalMessageList, quote) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Sezzle_Payment/payment/sezzle'
            },

            getSezzleImgSrc: function () {
                return 'https://d3svog4tlx445w.cloudfront.net/branding/sezzle-logos/png/sezzle-logo-sm-100w.png';
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
             * Get Checkout Message based on the currency
             * @returns {*}
             */
            getPaymentText: function () {
                return 'Payment Schedule';
            },

            redirectToSezzleController: function (data) {

                // Make a post request to redirect
                var url = mageUrl.build("sezzle/payment/redirect");

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
                            location.href = jsonData.redirectURL;
                        } else if (typeof jsonData['message'] !== 'undefined') {
                            globalMessageList.addErrorMessage({
                                'message': jsonData['message']
                            });
                        }
                    }
                });
            },

            handleRedirectAction: function () {
                var data = $("#co-shipping-form").serialize();
                if (!customer.isLoggedIn()) {
                    var email = quote.guestEmail;
                    data += '&email=' + email;
                }
                this.redirectToSezzleController(data);
            },

            continueToSezzle: function () {
                if (this.validate() && additionalValidators.validate()) {
                    this.handleRedirectAction();
                }
            },

            placeOrder: function(data, event) {
                this.continueToSezzle();
            }
        });
    }
);
