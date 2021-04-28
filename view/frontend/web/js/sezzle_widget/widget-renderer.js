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
        is_cart: false,
        widget_type: "standard",
        widget_url: null,

        initialize: function () {
            this._super();
            switch (this.widget_type) {
                case "standard":
                    if (!!!this.widget_url) {
                        console.warn('Sezzle: widget url not set, cannot render widget');
                        break;
                    }
                    this.processLegacySezzleWidget();
                    break;
                case "installment":
                    if (window.checkoutConfig.payment.sezzlepay === 'undefined') {
                        break;
                    }
                    var pricePath = window.checkoutConfig.payment.sezzlepay.installmentWidgetPricePath;
                    if (!pricePath) {
                        break;
                    }
                    this.processInstallmentWidget(pricePath);
                    break;
            }
        },

        // get currency symbol
        getCurrencySymbol: function (priceText) {
            if (window.checkoutConfig.payment.sezzlepay.currencySymbol) {
                return window.checkoutConfig.payment.sezzlepay.currencySymbol;
            }
            for (var i = 0; i < priceText.length; i++) {
                if (/[$|€||£|₤|₹]/.test(priceText[i])) {
                    return priceText[i];
                }
                // use this instead if on ISO-8859-1, expanding to include any applicable currencies
                // https://html-css-js.com/html/character-codes/currency/
                // if(priceText[i] == String.fromCharCode(8364)){ //€ = 8364, 128 = , 163 = £, 8377 = ₹
                // 	currency = String.fromCharCode(8364)
                // }
            }
            return '$';
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

        // process sezzle widget from sezzle server
        processLegacySezzleWidget: function () {
            console.log("Sezzle widget rendering started from Sezzle end");

            var script = document.createElement('script');
            script.type = 'text/javascript';
            script.src = this.widget_url;
            $("head").append(script);

            console.log("dom loaded");
        },

        // process sezzle installment widget in checkout page from host server
        processInstallmentWidget: function (pricePath) {
            setInterval(() => {
                this.addInstallmentWidgetContainer(pricePath);
                var priceElement = document.querySelector(pricePath),
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
                    currencySymbol = this.getCurrencySymbol(currentTotal),
                    includeComma = this.commaDelimited(currentTotal),
                    price = this.parsePriceString(currentTotal, includeComma),
                    installmentAmount = (price / 4).toFixed(2);
                for (var i = 0; i < priceElements.length; i++) {
                    if (i === priceElements.length - 1) {
                        installmentAmount = (price - installmentAmount * 3).toFixed(2);
                    }
                    priceElements[i].innerText = currencySymbol + (includeComma ? installmentAmount.replace('.', ',') : installmentAmount);
                }
            }, 250)

        },

        // add installment widget container inside sezzle payment section
        addInstallmentWidgetContainer: function (pricePath) {
            var sezzlePaymentLine = document.querySelector('#sezzle-method');
            if (!document.getElementById('sezzle-installment-widget-box') && sezzlePaymentLine) {
                $.cookieStorage.set('sezzle-total', document.querySelector(pricePath).innerText);
                sezzlePaymentLine = sezzlePaymentLine.getElementsByClassName("payment-method-content")[0];
                var sezzleCheckoutWidget = document.createElement('div');
                sezzleCheckoutWidget.id = 'sezzle-installment-widget-box';
                sezzlePaymentLine.insertBefore(sezzleCheckoutWidget, sezzlePaymentLine.lastElementChild);
            }
        }
    });
});
