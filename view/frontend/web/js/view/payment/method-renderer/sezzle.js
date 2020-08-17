/**
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
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
                template: 'Sezzle_Sezzlepay/payment/sezzle'
            },

            /**
             * Check is customer uuid is available
             * @returns bool
             */
            hasCustomerUUID: function () {
                var customerCustomAttributes = customer.customerData.custom_attributes;
                console.log(customerCustomAttributes);
                return !(customerCustomAttributes === undefined
                    || customerCustomAttributes.sezzle_customer_uuid === undefined
                    || !customerCustomAttributes.sezzle_customer_uuid.value);
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
             * Handle ajax action of the redirection
             */
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

            /**
             * Handle redirection
             */
            handleRedirectAction: function () {
                var data = $("#co-shipping-form").serialize();
                data += '&form_key='+$.mage.cookies.get('form_key');
                if (!customer.isLoggedIn()) {
                    var email = quote.guestEmail;
                    data += '&email=' + email;
                }
                this.redirectToSezzleController(data);
            },

            /**
             * Place Order click event
             */
            continueToSezzle: function () {
                if (this.validate() && additionalValidators.validate()) {
                    var loaderMsg = this.getLoaderMsg(),
                        msgPart1 = "<div><style>.page-loader{display:-ms-flexbox;display:flex;-ms-flex-direction:column;flex-direction:column;-ms-flex-pack:center;justify-content:center;position:fixed;top:0;left:0;z-index:10000000;width:100vw;height:100vh;background-color:#fff}.page-loader .loader-image{width:auto;height:120px;background-image:url(https://media.sezzle.com/branding/2.0/styleGuide/loader.svg);background-repeat:no-repeat;background-position:50%;background-size:80px auto}.page-loader .loader-text{font-size:18px;font-weight:normal;text-align:center;color:#252525}</style><div class='page-loader'><div class='loader-image'></div><div class='loader-text'>",
                        msgPart2 = "</div></div></div>";
                    $("body").append(msgPart1.concat(loaderMsg, msgPart2));
                    this.handleRedirectAction();
                }
            }
        });
    }
);
