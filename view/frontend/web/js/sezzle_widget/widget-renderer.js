/**
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */
define([
    'jquery',
    'ko',
    'uiComponent',
    'Magento_Checkout/js/model/quote',
    'Magento_SalesRule/js/model/coupon',
    'sezzleWidgetCore',
    'domReady!'
], function ($, ko, Component, quote, coupon) {
    'use strict';

    var totals = quote.getTotals(),
        couponCode = coupon.getCouponCode(),
        isApplied = coupon.getIsApplied();

    if (totals()) {
        couponCode(totals()['coupon_code']);
    }
    isApplied(couponCode() != null);

    return Component.extend({
        is_static_widget: false,
        merchant_uuid: null,
        is_cart: false,
        widget_type: "standard",
        price_path: null,
        isApplied: isApplied,

        initialize: function () {
            this._super();
            if (this.widget_type === "installment") {
                setInterval(() => {
                    console.log(isApplied())
                }, 300)
                this.processInstallmentWidget();
                return;
            }
            if (this.is_static_widget) {
                if (!this.is_cart) {
                    this.processStaticSezzleWidget();
                    return;
                }
                setInterval(() => {
                    if (document.getElementById("sezzle-widget")
                        && !document.getElementById("sezzle-widget").innerHTML) {
                        this.processStaticSezzleWidget();
                    }
                }, 300)
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
        },

        processInstallmentWidget: function() {
            return;
        },
    });
});
