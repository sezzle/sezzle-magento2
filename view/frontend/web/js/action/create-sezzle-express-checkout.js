/**
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

/**
 * @api
 */
define([
  "Magento_Checkout/js/model/quote",
  "Magento_Customer/js/customer-data",
  "Magento_Checkout/js/model/url-builder",
  "Sezzle_Sezzlepay/js/model/service-processor",
  "Magento_CheckoutAgreements/js/model/agreements-assigner",
], function (
  quote,
  customerData,
  urlBuilder,
  serviceProcessor,
  agreementsAssigner
) {
  "use strict";

  return function (paymentData, messageContainer) {
    var serviceUrl, payload;

    agreementsAssigner(paymentData);
    payload = {
      cartId: quote.getQuoteId(),
      paymentMethod: paymentData,
    };

    var customerObservable = customerData.get("customer");
    var customer = customerObservable();

    if (customer.firstname && customer.firstname !== "") {
      serviceUrl = urlBuilder.createUrl(
        "/sezzle/carts/mine/express-checkout",
        {}
      );
    } else {
      serviceUrl = urlBuilder.createUrl(
        "/sezzle/guest-carts/:quoteId/express-checkout",
        {
          quoteId: quote.getQuoteId(),
        }
      );
    }

    return serviceProcessor(serviceUrl, payload, messageContainer);
  };
});
