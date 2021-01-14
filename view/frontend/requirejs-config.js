/**
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */
var config = {
    map: {
        '*': {
            widgetRenderer: 'Sezzle_Sezzlepay/js/sezzle_widget/widget-renderer'
        }
    },
    paths: {
        sezzleInContextCheckout: 'https://checkout-sdk.sezzle.com/checkout'
    },
    shim: {
        sezzleInContextCheckout: {
            exports: 'sezzle'
        }
    }
};
