/**
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */
define([
  "uiComponent",
  "jquery",
  "Magento_Customer/js/model/customer",
  "Sezzle_Sezzlepay/js/express-checkout/express-checkout-wrapper",
  "Sezzle_Sezzlepay/js/action/create-sezzle-checkout",
  "Sezzle_Sezzlepay/js/action/create-sezzle-customer-order",
  "Magento_Checkout/js/action/redirect-on-success",
], function (
  Component,
  $,
  customer,
  ExpressCheckoutWrapper,
  createSezzleCheckoutAction,
  createSezzleCustomerOrder,
  redirectOnSuccessAction
) {
  "use strict";

  return Component.extend(ExpressCheckoutWrapper).extend({
    defaults: {
      template: "Sezzle_Sezzlepay/cart/express-checkout",
    },

    /**
     * Check is customer uuid is available
     *
     * @returns bool
     */
    hasCustomerUUID: function () {
      var customerCustomAttributes = customer.customerData.custom_attributes;
      return (
        customerCustomAttributes !== undefined &&
        customerCustomAttributes.sezzle_customer_uuid !== undefined &&
        customerCustomAttributes.sezzle_customer_uuid.value
      );
    },

    /**
     * Get Place Order button name
     *
     * @returns string
     */
    getSubmitButtonName: function () {
      return this.hasCustomerUUID() ? "Place Order" : "Continue to Sezzle";
    },

    /**
     * Get loader message
     *
     * @returns string
     */
    getLoaderMsg: function () {
      return this.hasCustomerUUID()
        ? "Placing your order..."
        : "Redirecting you to Sezzle Checkout...";
    },

    /**
     * Get Sezzle Image src
     *
     * @returns string
     */
    getSezzleImgSrc: function () {
      return window.checkoutConfig.payment.sezzlepay.img_src;
    },

    /**
     * Get payment method data
     *
     * @returns {Object}
     */
    getData: function () {
      return {
        method: "sezzlepay",
        additional_data: null,
        po_number: null,
      };
    },

    /**
     *
     * Handle redirection
     */
    handleRedirectAction: function () {
      var self = this;

      self.isPlaceOrderActionAllowed(false);

      // created order by customer UUID if customer is tokenized
      if (customer.isLoggedIn() && this.hasCustomerUUID()) {
        this.getCreateSezzleCustomerOrderDeferredObject()
          .done(function (response) {
            var jsonResponse = $.parseJSON(response);
            if (jsonResponse.checkout_url) {
              $.mage.redirect(jsonResponse.checkout_url); // if tokenization fails, we create checkout and return the checkout URL
              return;
            }

            redirectOnSuccessAction.execute(); // successful customer order
          })
          .always(function () {
            self.isPlaceOrderActionAllowed(true);
          });
        return;
      }

      // creates standard checkout
      this.getCreateSezzleCheckoutDeferredObject()
        .done(function (response) {
          var jsonResponse = $.parseJSON(response);
          $.mage.redirect(jsonResponse.checkout_url);
        })
        .always(function () {
          self.isPlaceOrderActionAllowed(true);
        });
    },

    /**
     * Creates Sezzle order by customer UUID
     *
     * @return {*}
     */
    getCreateSezzleCustomerOrderDeferredObject: function () {
      return $.when(
        createSezzleCustomerOrder(this.getData(), this.messageContainer)
      );
    },

    /**
     * Creates Sezzle Checkout
     *
     * @return {*}
     */
    getCreateSezzleCheckoutDeferredObject: function () {
      return $.when(
        createSezzleCheckoutAction(this.getData(), this.messageContainer)
      );
    },

    /**
     * Place Order click event
     */
    continueToSezzle: function (data, event) {
      if (event) {
        event.preventDefault();
      }

      this.handleRedirectAction();
    },
  });
});
