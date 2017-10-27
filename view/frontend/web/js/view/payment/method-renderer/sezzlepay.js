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

            redirectToSezzlepayController: function(sUrl) {
                window.location.replace(window.checkoutConfig.payment.sezzlepay.redirectUrl);
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
                if (this.validate() && additionalValidators.validate()) {
                    this.handleRedirectAction('sezzlepay/standard/redirect/');
                    return false;
                }
            },
        });
    }
);