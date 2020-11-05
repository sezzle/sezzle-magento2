/**
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */
var config = {
    map: {
        '*': {
            sezzleWidgetCore: 'Sezzle_Sezzlepay/js/sezzle_widget/sezzle-widget-core',
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
