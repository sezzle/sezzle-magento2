/**
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */
define([
  "uiComponent",
  "jquery",
  "ko",
  "Magento_Customer/js/model/customer",
  "Magento_Customer/js/customer-data",
  "Sezzle_Sezzlepay/js/express-checkout/minicart-express-checkout-wrapper",
  "Sezzle_Sezzlepay/js/action/create-sezzle-express-checkout",
  "Sezzle_Sezzlepay/js/action/create-sezzle-customer-order",
  "Magento_Checkout/js/action/redirect-on-success",
], function (
  Component,
  $,
  ko,
  customer,
  customerData,
  MinicartExpressCheckoutWrapper,
  createSezzleExpressCheckoutAction,
  createSezzleCustomerOrder,
  redirectOnSuccessAction
) {
  "use strict";

  return Component.extend(MinicartExpressCheckoutWrapper).extend({
    defaults: {
      template: "Sezzle_Sezzlepay/minicart/express-checkout",
    },

    /**
     * Initialize component
     */
    initialize: function () {
      this._super();
      var self = this;

      // Create observable for cart items
      this.cartHasItems = ko.observable(false);

      // Subscribe to cart data changes
      var cart = customerData.get("cart");
      console.log("cart", cart);

      // Initial check
      this.cartHasItems(cart().items && cart().items.length > 0);

      // Listen for cart updates
      cart.subscribe(function (updatedCart) {
        var hasItems = updatedCart.items && updatedCart.items.length > 0;
        self.cartHasItems(hasItems);
      });

      return this;
    },

    /**
     * Check if button should be visible
     *
     * @returns {boolean}
     */
    isVisible: function () {
      return this.cartHasItems();
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
        createSezzleExpressCheckoutAction(this.getData(), this.messageContainer)
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
