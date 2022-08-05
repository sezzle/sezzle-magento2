/**
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */
define([
    'jquery',
    'ko',
    'uiComponent',
    'domReady!'
], function ($, ko, Component) {
    'use strict';

    return Component.extend({
        is_cart: false,
        widget_url: null,

        initialize: function () {
            this._super();
            if (!!!this.widget_url) {
                console.warn('Sezzle: widget url not set, cannot render widget');
                return;
            }
            this.processLegacySezzleWidget();
        },

        // default widget config for sezzle
        addDefaultConfig: function () {
            document.sezzleConfig = {
                "configGroups": [{
                    "targetXPath": ".product-info-main/.price-final_price/.price",
                    "renderToPath": "../../../..",
                    "relatedElementActions": [
                        {
                            "relatedPath": "..",
                            "initialAction": function (r, w) {
                                if (r.dataset.priceType === 'oldPrice') {
                                    w.style.display = "none"
                                }
                            }
                        }
                    ]
                }, {
                    "targetXPath": ".amount/STRONG-0/.price",
                    "renderToPath": "../../../../..",
                    "urlMatch": "cart"
                }]
            }
        },

        // process sezzle widget from sezzle server
        processLegacySezzleWidget: function () {
            console.log("Sezzle widget rendering started from Sezzle end");
            this.addDefaultConfig();
            var script = document.createElement('script');
            script.type = 'text/javascript';
            script.src = this.widget_url;
            $("head").append(script);
            console.log("dom loaded");
        }
    });
});
