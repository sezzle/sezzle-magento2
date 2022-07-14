/**
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        var allowInContextCheckout = window.checkoutConfig.payment.sezzlepay.allowInContextCheckout,
            isAheadworksCheckoutEnabled = window.checkoutConfig.payment.sezzlepay.isAheadworksCheckoutEnabled,
            sezzleComponent = 'Sezzle_Sezzlepay/js/view/payment/method-renderer' +
                ((allowInContextCheckout && !isAheadworksCheckoutEnabled) ? '/in-context/sezzle' : '/sezzle');

        rendererList.push(
            {
                type: 'sezzlepay',
                component: sezzleComponent
            }
        );

        /** Add view logic here if needed */
        return Component.extend({});
    }
);
