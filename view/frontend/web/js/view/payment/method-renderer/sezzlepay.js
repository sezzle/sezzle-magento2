/*browser:true*/
/*global define*/
define(
    [
        'Magento_Checkout/js/view/payment/default',
		'Magento_Checkout/js/action/place-order',
		'mage/url',
    ],
    function (Component,placeOrderAction,url) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Sezzle_Pay/payment/sezzlepay'
            },

			afterPlaceOrder: function () {
                window.location.replace(url.build('sezzle/redirect/'));
			},
        });
    }
);