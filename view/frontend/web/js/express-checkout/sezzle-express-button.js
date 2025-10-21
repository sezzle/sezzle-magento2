
/**
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */
define([
  "jquery",
  "Magento_Checkout/js/model/error-processor",
  "Magento_Checkout/js/model/full-screen-loader",
  "expressCheckoutSDK",
], function ($, errorProcessor, fullScreenLoader) {
  "use strict";

  return function (clientConfig) {
    var checkoutSDK = new Checkout(
      clientConfig.rendererComponent.getSDKConfig()
    );

    checkoutSDK.renderSezzleButton(clientConfig.sezzleButtonContainerElementID);
}
}
);