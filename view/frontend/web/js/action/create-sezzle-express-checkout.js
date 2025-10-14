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
  "Magento_Customer/js/model/customer",
  "Magento_Checkout/js/model/url-builder",
  "Sezzle_Sezzlepay/js/model/service-processor",
  "Magento_CheckoutAgreements/js/model/agreements-assigner",
], function (
  quote,
  customer,
  urlBuilder,
  serviceProcessor,
  agreementsAssigner
) {
  "use strict";

  return function (paymentData, messageContainer) {
    var serviceUrl, payload, billingAddress;

    console.log("paymentData", paymentData);

    agreementsAssigner(paymentData);
    payload = {
      cartId: quote.getQuoteId(),
      billingAddress: {
        countryId: "US",
        regionId: "4",
        regionCode: "AZ",
        region: "Arizona",
        street: ["tets", ""],
        company: "ttest",
        telephone: "3029830384",
        postcode: "12345",
        city: "test",
        firstname: "test",
        lastname: "tes",
        saveInAddressBook: null,
      },
      paymentMethod: paymentData,
    };

    if (customer.isLoggedIn()) {
      serviceUrl = urlBuilder.createUrl("/sezzle/carts/mine/checkout", {});
    } else {
      serviceUrl = urlBuilder.createUrl(
        "/sezzle/guest-carts/:quoteId/checkout",
        {
          quoteId: quote.getQuoteId(),
        }
      );
      payload.email = quote.guestEmail || "test@test.com";
    }
    console.log("payload", payload);

    return serviceProcessor(serviceUrl, payload, messageContainer);
  };
});
