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
                var component = clientConfig.rendererComponent,
                    blocker = typeof component.getCheckoutBlocker === 'function'
                        ? component.getCheckoutBlocker()
                        : null;

                event.preventDefault();

                // Surface anything we already know is wrong before the modal opens. The
                // modal covers the page with a loader, so an error raised behind it is
                // invisible and the shopper is left watching a spinner.
                if (blocker) {
                    errorProcessor.process({
                        responseText: JSON.stringify({message: blocker})
                    }, component.messageContainer);

                    return;
                }

                // openModal() stays synchronous inside the click handler so the browser
                // does not treat the popup as unsolicited.
                checkoutSDK.openModal();
                component.validateCheckout().done(function () {
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
