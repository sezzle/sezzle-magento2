define([
    'jquery',
    'ko',
    'uiComponent',
    'domReady!'
], function ($, ko, Component) {
    'use strict';

    return Component.extend({
        initialize: function () {
            //initialize parent Component
            this._super();
            this.processSezzleDocument();
        },

        processSezzleDocument: function() {
            console.log("rendering started");
            var self = this;
            console.log(self.jsConfig);
            document.sezzleConfig = self.jsConfig;

            if (!document.sezzleConfig) {
                console.warn('SezzlePay: document.sezzleConfig is not set, cannot render widget');
                return;
            }

            var script = document.createElement('script');
            script.type = 'text/javascript';
            script.src = 'https://widget.sezzle.com/v1/javascript/price-widget?uuid=' + document.sezzleConfig.merchantID;
            $("head").append(script);

            console.log("dom loaded");
        }
    });
});