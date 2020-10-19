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
        sezzleInContextCheckout: 'http://localhost/checkout-sdk-web/build/checkout.min'
    },
    shim: {
        sezzleInContextCheckout: {
            exports: 'sezzle'
        }
    }
};
