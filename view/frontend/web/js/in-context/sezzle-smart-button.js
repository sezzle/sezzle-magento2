/**
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */
define([
    'jquery',
    'Magento_Checkout/js/model/error-processor',
    'Magento_Checkout/js/model/full-screen-loader',
    'checkoutSDK'
], function ($, errorProcessor, fullScreenLoader) {
    'use strict';

    return function (clientConfig) {
        var checkoutSDK = new Checkout(clientConfig.rendererComponent.getSDKConfig());

        checkoutSDK.renderSezzleButton(clientConfig.sezzleButtonContainerElementID);
        checkoutSDK.init({
            onClick: function (event) {
                event.preventDefault();
                clientConfig.rendererComponent.validateCheckout().done(function () {
                    fullScreenLoader.startLoader();
                    clientConfig.rendererComponent.beforeOnClick().done(function (response) {
                        var jsonResponse = $.parseJSON(response);
                        checkoutSDK.startCheckout({
                            checkout_url: jsonResponse.checkout_url
                        });
                    }).fail(
                        function (response) {
                            errorProcessor.process(response, this.messageContainer);
                        }
                    ).always(function () {
                        fullScreenLoader.stopLoader();
                    })
                })

            },
            onComplete: function () {
                clientConfig.rendererComponent.afterOnComplete();
            },
            onCancel: function () {
                clientConfig.rendererComponent.catchOnCancel();
            },
            onFailure: function () {
                clientConfig.rendererComponent.catchOnFailure();
            }
        });
    };
});
