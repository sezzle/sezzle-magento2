/*
 * @category    Sezzle
 * @package     Sezzle_Payment
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
        rendererList.push(
            {
                type: 'sezzle',
                component: 'Sezzle_Payment/js/view/payment/method-renderer/sezzle'
            }
        );

        /** Add view logic here if needed */
        return Component.extend({});
    }
);
