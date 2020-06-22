/**
 * @category    Sezzle
 * @package     Sezzle_Payment
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
        initialize: function () {
            this._super();
            this.processSezzleWidget();
        },

        processSezzleWidget: function() {
            const renderSezzle = new AwesomeSezzle({
                amount: this.price,
                alignment: this.alignment
            });
            renderSezzle.init();
            console.log("Sezzle widget is rendered.");
        }
    });
});
