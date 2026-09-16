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
                    validation,
                    settledInGesture;

                event.preventDefault();

                // The modal has to be opened from inside the user gesture or the browser
                // blocks it as an unsolicited popup, which is what PLAT-3425 fixed.
                //
                // Validating first is still preferable where we can: the modal covers the
                // page with its own loader, so an error raised behind it is invisible, and
                // closing it in the same tick it opened does not reliably take effect.
                //
                // Whether we can do both depends on when validation settles, not on which
                // checkout is installed. The native path resolves its Deferred before
                // returning, so its .done() runs inside the gesture. The Aheadworks branch
                // returns that checkout's own _beforeAction(), which can settle after an
                // async save. So ask the Deferred: if it has already settled the gesture is
                // still ours and validation gets to run first, and if it is pending we open
                // now and close again should it reject. A thenable without state() is
                // treated as pending, since that is the assumption that keeps the gesture.
                validation = component.validateCheckout();
                settledInGesture = typeof validation.state === 'function'
                    && validation.state() !== 'pending';

                if (!settledInGesture) {
                    checkoutSDK.openModal();
                }

                validation.done(function () {
                    if (settledInGesture) {
                        checkoutSDK.openModal();
                    }

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
                    // Only ever closing a modal this click opened, and only from a later
                    // tick than the one that opened it. Never leave the shopper on a
                    // spinning modal with no way back.
                    if (!settledInGesture) {
                        checkoutSDK.closeModal();
                    }
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
