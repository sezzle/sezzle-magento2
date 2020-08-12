/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'jquery',
    'underscore',
    'sezzleInContextCheckout',
    'Magento_Checkout/js/model/error-processor',
    'Magento_Checkout/js/model/full-screen-loader'
], function ($, _, sezzle, errorProcessor, fullScreenLoader) {
    'use strict';

    // /**
    //  * Returns array of allowed funding
    //  *
    //  * @param {Object} config
    //  * @return {Array}
    //  */
    // function getFunding(config) {
    //     return _.map(config, function (name) {
    //         return paypal.FUNDING[name];
    //     });
    // }

    return function (clientConfig, element) {
        console.log("Sezzle India");
        console.log(clientConfig.rendererComponent.getSDKConfig());
        var checkoutSDK = new Checkout(clientConfig.rendererComponent.getSDKConfig());
        checkoutSDK.renderSezzleButton("sezzle-smart-button-container");
        checkoutSDK.init({
            onClick: function () {
                event.preventDefault();
                console.log("onClick");
                fullScreenLoader.startLoader();
                clientConfig.rendererComponent.beforeOnClick().success(function (response) {
                    var jsonResponse = $.parseJSON(response);
                    console.log(jsonResponse);
                    checkoutSdk.renderModal(jsonResponse.redirectURL);
                }).always(function () {
                    fullScreenLoader.stopLoader();
                }).fail(
                    function (response) {
                        errorProcessor.process(response, this.messageContainer);
                    }
                )
            },
            onComplete: function () {
                fullScreenLoader.startLoader();
                //checkoutSdk.capturePayment(clientConfig.rendererComponent.getCaptureObject());
                clientConfig.rendererComponent.afterOnComplete().success(function (response) {
                    $.mage.redirect(response.confirmation_url);
                }).always(function () {
                    fullScreenLoader.stopLoader();
                }).fail(
                    function (response) {
                        errorProcessor.process(response, this.messageContainer);
                    }
                )
            }
        });
    };
});
