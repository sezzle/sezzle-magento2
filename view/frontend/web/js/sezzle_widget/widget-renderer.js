/**
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */
define([
    'jquery',
    'ko',
    'uiComponent',
    'sezzleWidgetCore',
    'domReady!'
], function ($, ko, Component) {
    'use strict';

    return Component.extend({
        is_static_widget: false, merchant_uuid: null,

        initialize: function () {
            this._super();
            if (this.is_static_widget) {
                this.processStaticSezzleWidget();
            } else {
                if (this.merchant_uuid === null || this.merchant_uuid === 0) {
                    console.warn('Sezzle: merchant uuid not set, cannot render widget');
                    return;
                }
                this.processLegacySezzleWidget();
            }
        },

        processStaticSezzleWidget: function() {
            console.log("Sezzle widget rendering started from host server");
            const renderSezzle = new AwesomeSezzle({
                amount: this.price,
                alignment: this.alignment
            });
            renderSezzle.init();
            console.log("Sezzle widget is rendered.");
        },

        processLegacySezzleWidget: function() {
            console.log("Sezzle widget rendering started from Sezzle end");

            var script = document.createElement('script');
            script.type = 'text/javascript';
            script.src = 'https://widget.sezzle.com/v1/javascript/price-widget?uuid=' + this.merchant_uuid;
            $("head").append(script);

            console.log("dom loaded");
        }
    });
});
