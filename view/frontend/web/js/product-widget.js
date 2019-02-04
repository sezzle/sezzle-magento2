require([
    "jquery",
    "domReady!"
    ],
    function ($, alert) {
    'use strict';
    $.widget('mage.productWidget', {
        _create: function () {
            console.log("rendering started");
            var self = this;
            document.sezzleConfig = self.options.jsConfig;

            if (!document.sezzleConfig) {
                console.warn('SezzlePay: document.sezzleConfig is not set, cannot render widget');
                return;
            }

            var script = document.createElement('script');
            script.type = 'text/javascript';
            script.src = 'https://widget.sezzle.com/v1/javascript/price-widget?uuid=' + document.sezzleConfig.merchantID;
            document.head.appendChild(script);

            console.log("dom loaded");

        }
    });
    return $.mage.productWidget
});