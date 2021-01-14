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

    $.cookieStorage.setConf({
        path: '/',
        expires: 1
    });

    return Component.extend({
        merchant_uuid: null,
        is_cart: false,
        widget_type: "standard",
        price_path: window.checkoutConfig.payment.sezzlepay.installmentWidgetPricePath,

        initialize: function () {
            this._super();
            switch (this.widget_type) {
                case "standard":
                    if (this.merchant_uuid === null || this.merchant_uuid === 0) {
                        console.warn('Sezzle: merchant uuid not set, cannot render widget');
                        break;
                    }
                    this.processLegacySezzleWidget();
                    break;
                case "installment":
                    this.processInstallmentWidget();
                    break;
            }
        },

        // checks if price is comma (fr) format or period (en)
        commaDelimited: function (priceText) {
            var priceOnly = '';
            for (var i = 0; i < priceText.length; i++) {
                if (this.isNumeric(priceText[i]) || priceText[i] === '.' || priceText[i] === ',') {
                    priceOnly += priceText[i];
                }
            }
            var isComma = false;
            if (priceOnly.indexOf(',') > -1 && priceOnly.indexOf('.') > -1) {
                isComma = priceOnly.indexOf(',') > priceOnly.indexOf('.');
            } else if (priceOnly.indexOf(',') > -1) {
                isComma = priceOnly[priceOnly.length - 3] === ',';
            } else if (priceOnly.indexOf('.') > -1) {
                isComma = priceOnly[priceOnly.length - 3] !== '.';
            } else {
                isComma = false;
            }
            return isComma;
        },

        // checks if character is numeric
        isNumeric: function (n) {
            return !isNaN(parseFloat(n)) && isFinite(n);
        },

        // parses the checkout total text to numerical digits only
        parsePriceString: function (price, includeComma) {
            var formattedPrice = '';
            for (var i = 0; i < price.length; i++) {
                if (this.isNumeric(price[i]) || (!includeComma && price[i] === '.') || (includeComma && price[i] === ',')) {
                    // If current is a . and previous is a character, it can be something like Rs, ignore it
                    if (i > 0 && price[i] === '.' && /^[a-zA-Z()]+$/.test(price[i - 1])) continue;
                    formattedPrice += price[i];
                }
            }
            if (includeComma) {
                formattedPrice.replace(',', '.');
            }
            return parseFloat(formattedPrice);
        },

        // process sezzle widget from host server
        processStaticSezzleWidget: function () {
            console.log("Sezzle widget rendering started from host server");
            const renderSezzle = new AwesomeSezzle({
                amount: this.price,
                alignment: this.alignment
            });
            renderSezzle.init();
            console.log("Sezzle widget is rendered.");
        },

        // process sezzle widget from sezzle server
        processLegacySezzleWidget: function () {
            console.log("Sezzle widget rendering started from Sezzle end");

            var script = document.createElement('script');
            script.type = 'text/javascript';
            script.src = 'https://widget.sezzle.com/v1/javascript/price-widget?uuid=' + this.merchant_uuid;
            $("head").append(script);

            console.log("dom loaded");
        },

        // process sezzle installment widget in checkout page from host server
        processInstallmentWidget: function () {
            setInterval(() => {
                this.addInstallmentWidgetContainer();
                var priceElement = document.querySelector(this.price_path),
                    installmentPriceElement = document.getElementsByClassName("sezzle-payment-schedule-prices")[0];
                if (!priceElement || !priceElement.innerText) {
                    priceElement = document.querySelector(".estimated-price");
                }
                if (!priceElement || !installmentPriceElement) {
                    return;
                }
                var currentTotal = priceElement.innerText,
                    storedTotal = $.cookieStorage.get('sezzle-total');
                if (storedTotal.localeCompare(currentTotal) === 0) {
                    return;
                }
                $.cookieStorage.set('sezzle-total', currentTotal);
                var priceElements = installmentPriceElement.children,
                    includeComma = this.commaDelimited(currentTotal),
                    price = this.parsePriceString(currentTotal, includeComma),
                    installmentAmount = (price / 4).toFixed(2);
                for (var i = 0; i < priceElements.length; i++) {
                    if (i === priceElements.length - 1) {
                        installmentAmount = (price - installmentAmount * 3).toFixed(2);
                    }
                    priceElements[i].innerText = '$' + (includeComma ? installmentAmount.replace('.', ',') : installmentAmount);
                }
            }, 250)

        },

        // add installment widget container inside sezzle payment section
        addInstallmentWidgetContainer: function () {
            var sezzlePaymentLine = document.querySelector('#sezzle-method');
            if (!document.getElementById('sezzle-installment-widget-box') && sezzlePaymentLine) {
                $.cookieStorage.set('sezzle-total', document.querySelector(this.price_path).innerText);
                sezzlePaymentLine = sezzlePaymentLine.getElementsByClassName("payment-method-content")[0];
                var sezzleCheckoutWidget = document.createElement('div');
                sezzleCheckoutWidget.id = 'sezzle-installment-widget-box';
                sezzlePaymentLine.insertBefore(sezzleCheckoutWidget, sezzlePaymentLine.lastElementChild);
            }
        }
    });
});
