/**
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */
define([
    'jquery',
    'sezzleInContextCheckout',
    'Magento_Checkout/js/model/error-processor',
    'Magento_Checkout/js/model/full-screen-loader'
], function ($, sezzle, errorProcessor, fullScreenLoader) {
    'use strict';

    return function (clientConfig, element) {
        var checkoutSDK = new Checkout(clientConfig.rendererComponent.getSDKConfig());

        checkoutSDK.renderSezzleButton(clientConfig.sezzleButtonContainerElementID);
        checkoutSDK.init({
            onClick: function () {
                event.preventDefault();
                clientConfig.rendererComponent.validateCheckout().done(function () {
                    fullScreenLoader.startLoader();
                    clientConfig.rendererComponent.beforeOnClick().success(function (response) {
                        var jsonResponse = $.parseJSON(response);
                        checkoutSDK.startCheckout({
                            checkout_url: jsonResponse.checkout_url
                        });
                    }).always(function () {
                        fullScreenLoader.stopLoader();
                    }).fail(
                        function (response) {
                            errorProcessor.process(response, this.messageContainer);
                        }
                    )
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
