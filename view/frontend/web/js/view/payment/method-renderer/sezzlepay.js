/*browser:true*/
/*global define*/
define(
    [
        'Magento_Checkout/js/view/payment/default',
        'jquery',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/action/set-payment-information',
        'mage/url',
        'mage/translate',
        'Magento_Checkout/js/checkout-data',
        'Magento_Checkout/js/action/select-payment-method',
        'Magento_Ui/js/model/messageList'
    ],
    function (Component, $, additionalValidators, setPaymentInformationAction, mageUrl, $t, checkoutData, selectPaymentMethodAction, globalMessageList) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Sezzle_Sezzlepay/payment/sezzlepay'
            },

            getSezzlepayImgSrc: function () {
                return 'https://d3svog4tlx445w.cloudfront.net/branding/sezzle-logos/png/sezzle-logo-sm-100w.png';
            },

            redirectToSezzlepayController: function (data) {

                // Make a post request to redirect
                var url = mageUrl.build("sezzlepay/standard/redirect");

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
                if (!window.checkoutConfig.quoteData.customer_id) {
                    var email = document.getElementById("customer-email").value;
                } else {
                    var email = window.checkoutConfig.customerData.email;
                }
                var data = data + '&email=' + email;
                this.redirectToSezzlepayController(data);
            },
            
            continueToSezzlepay: function () {
                if (this.validate() && additionalValidators.validate()) {
                    this.handleRedirectAction();
                }
            },

            placeOrder: function(data, event) {
                this.continueToSezzlepay();
            }
        });
    }
);