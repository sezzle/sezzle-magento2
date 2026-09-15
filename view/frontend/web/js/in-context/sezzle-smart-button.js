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
                var component = clientConfig.rendererComponent;

                event.preventDefault();

                // Validate before anything opens. The modal covers the page with its own
                // loader, so an error raised behind it is invisible and closing it from
                // the same tick it opened in does not reliably take effect.
                //
                // validateCheckout() settles synchronously, so openModal() still runs
                // within the click handler and is not treated as an unsolicited popup.
                component.validateCheckout().done(function () {
                    checkoutSDK.openModal();
                    fullScreenLoader.startLoader();
                    component.beforeOnClick().done(function (response) {
                        var jsonResponse = $.parseJSON(response);
                        checkoutSDK.startCheckout({checkout_url: jsonResponse.checkout_url});
                    }).fail(
                        function (response) {
                            errorProcessor.process(response, component.messageContainer);
                            checkoutSDK.closeModal();
                        }
                    ).always(function () {
                        fullScreenLoader.stopLoader();
                    })
                }).fail(function () {
                    // Never leave the shopper on a spinning modal with no way back.
                    checkoutSDK.closeModal();
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
