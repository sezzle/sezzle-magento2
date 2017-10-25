/*browser:true*/
/*global define*/
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
        rendererList.push(
            {
                type: 'sezzlepay',
                component: 'Sezzle_Sezzlepay/js/view/payment/method-renderer/sezzlepay'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);