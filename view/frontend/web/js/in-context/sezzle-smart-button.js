/**
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */
define([
    'jquery',
    'sezzleInContextCheckout',
    'Magento_Checkout/js/model/error-processor',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Checkout/js/action/set-billing-address',
    'Magento_Ui/js/model/messageList'
], function ($, sezzle, errorProcessor, fullScreenLoader, setBillingAddressAction, globalMessageList) {
    'use strict';

    return function (clientConfig, element) {
        var checkoutSDK = new Checkout(clientConfig.rendererComponent.getSDKConfig());

        checkoutSDK.renderSezzleButton(clientConfig.sezzleButtonContainerElementID);
        checkoutSDK.init({
            onClick: function () {
                event.preventDefault();
                fullScreenLoader.startLoader();
                setBillingAddressAction(globalMessageList).done(function () {
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
                }).fail(function (response) {
                    errorProcessor.process(response, this.messageContainer);
                    fullScreenLoader.stopLoader();
                });
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
