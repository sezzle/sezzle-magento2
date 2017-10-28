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
        'Magento_Checkout/js/action/select-payment-method'
    ],
    function (Component, $, additionalValidators, setPaymentInformationAction, url, $t, checkoutData, selectPaymentMethodAction) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Sezzle_Sezzlepay/payment/sezzlepay'
            },

            getSezzlepayImgSrc: function() {
                return 'https://d3svog4tlx445w.cloudfront.net/branding/sezzle-logos/png/sezzle-logo-sm-100w.png';
            },

            redirectToSezzlepayController: function(sUrl) {

                // Make a post request to redirect
                var url = window.checkoutConfig.payment.sezzlepay.redirectUrl;

                $.ajax({
                    url: url,
                    method:'post',
                    success: function(response) {
                        // Send this response to sezzle api
                        // This would redirect to sezzle
                        var jsonData = JSON.parse(response);
                        $.ajax({
                            url: jsonData.redirectURL,
                            method: 'post',
                            data: jsonData.data
                        });
                    }
                });
            },

            handleRedirectAction: function(sUrl) {
                var self = this;

                // update payment method information if additional data was changed
                this.selectPaymentMethod();
                this.isPlaceOrderActionAllowed(false);

                this.getPlaceOrderDeferredObject()
                .fail(
                    function () {
                        self.isPlaceOrderActionAllowed(true);
                    }
                ).done(
                    function () {
                        self.afterPlaceOrder();
                        self.redirectToSezzlepayController(sUrl);
                    }
                );
            },
            
            continueToSezzlepay: function () {
                console.log('config', JSON.stringify(window.checkoutConfig));
                if (this.validate() && additionalValidators.validate()) {
                    this.handleRedirectAction('sezzlepay/standard/redirect/');
                    return false;
                }
            },
        });
    }
);