/**
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */
define(
    [
        'jquery',
        'mage/translate',
        'uiRegistry',
        'Magento_Checkout/js/model/quote',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Sezzle_Sezzlepay/js/action/create-sezzle-checkout',
        'Sezzle_Sezzlepay/js/action/create-sezzle-customer-order',
        'Magento_Checkout/js/action/redirect-on-success',
    ],
    function (
        $,
        $t,
        registry,
        quote,
        customer,
        Component,
        additionalValidators,
        createSezzleCheckoutAction,
        createSezzleCustomerOrder,
        redirectOnSuccessAction) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Sezzle_Sezzlepay/payment/sezzle',
                billingAddressSkipped: false,
                isRequestPending: false
            },

            /**
             * Billing address fields the shopper fills in. Country is left out on
             * purpose - Magento pre-selects the store default, so an untouched form
             * always reports one.
             */
            billingAddressFields: [
                'firstname',
                'lastname',
                'company',
                'street',
                'city',
                'region',
                'region_id',
                'postcode',
                'telephone',
                'vat_id'
            ],

            /**
             * @returns {Component} Chainable.
             */
            initObservable: function () {
                this._super().observe(['billingAddressSkipped', 'isRequestPending']);

                return this;
            },

            /**
             * Billing address is optional for Sezzle. Magento blocks the place order
             * action as soon as the quote has no billing address, which happens the
             * moment the shopper unchecks "same as shipping". Track that case so the
             * Sezzle action stays available while the form is left untouched.
             *
             * @returns {Component} Chainable.
             */
            initialize: function () {
                var self = this;

                this._super();

                registry.async('checkoutProvider')(function (checkoutProvider) {
                    self.checkoutProvider = checkoutProvider;
                    checkoutProvider.on(
                        'billingAddress' + self.getCode(),
                        function () {
                            self.resolveBillingAddressSkipped();
                        },
                        'sezzleBillingAddress'
                    );
                    self.resolveBillingAddressSkipped();
                });

                quote.billingAddress.subscribe(function () {
                    self.resolveBillingAddressSkipped();
                });
                this.resolveBillingAddressSkipped();

                return this;
            },

            /**
             * Drop the billing address form subscription alongside the core ones
             */
            disposeSubscriptions: function () {
                this._super();

                registry.async('checkoutProvider')(function (checkoutProvider) {
                    checkoutProvider.off('sezzleBillingAddress');
                });
            },

            /**
             * Flag the billing address as deliberately skipped when the quote carries
             * no billing address and nothing has been typed into the form.
             *
             * A virtual quote has no shipping address, so its billing address is the
             * only one Magento can validate when the order is placed on the return leg
             * from Sezzle. Leave the button gated there so the shopper is stopped here
             * rather than after authorizing.
             */
            resolveBillingAddressSkipped: function () {
                this.billingAddressSkipped(
                    !quote.isVirtual()
                    && quote.billingAddress() === null
                    && this.isBillingAddressFormEmpty()
                );
            },

            /**
             * Check the live billing address form for shopper entered data
             *
             * @returns {Boolean}
             */
            isBillingAddressFormEmpty: function () {
                var data = this.checkoutProvider && this.checkoutProvider.get('billingAddress' + this.getCode());

                if (!data) {
                    return true;
                }

                return this.billingAddressFields.every(function (field) {
                    var value = data[field];

                    if ($.isArray(value)) {
                        value = value.join('');
                    }

                    return value === undefined || value === null || String(value).trim() === '';
                });
            },

            /**
             * Describe why the Sezzle action cannot run, or null when it can.
             *
             * Returning a message rather than a bare false lets callers show the
             * shopper something they can act on. Refusing silently strands them on a
             * disabled button or a spinning modal with no idea what is wrong.
             *
             * @returns {String|null}
             */
            getCheckoutBlocker: function () {
                if (this.isRequestPending()) {
                    return $t('Your request is still being processed. Please wait.');
                }

                if (this.isPlaceOrderActionAllowed()) {
                    return null;
                }

                // The quote has no billing address. Either the shopper deliberately left
                // it blank, which Sezzle allows, or they filled the form and never
                // pressed Update, so it was never committed. Falling back to the shipping
                // address in that second case would bill them somewhere they did not
                // choose, so ask them to commit or clear it instead.
                if (this.billingAddressSkipped()) {
                    return null;
                }

                if (quote.isVirtual()) {
                    return $t('Please enter a billing address.');
                }

                return $t(
                    'Your billing address has not been saved. Select Update below the billing '
                    + 'address form, or clear the form to use your shipping address.'
                );
            },

            /**
             * Whether the Sezzle action may run. Mirrors isPlaceOrderActionAllowed but
             * tolerates a deliberately blank billing address.
             *
             * @returns {Boolean}
             */
            isSezzleActionAllowed: function () {
                return this.getCheckoutBlocker() === null;
            },

            /**
             * Check is customer uuid is available
             *
             * @returns bool
             */
            hasCustomerUUID: function () {
                var customerCustomAttributes = customer.customerData.custom_attributes;
                return customerCustomAttributes !== undefined
                    && customerCustomAttributes.sezzle_customer_uuid !== undefined
                    && customerCustomAttributes.sezzle_customer_uuid.value;
            },

            /**
             * Get Place Order button name
             *
             * @returns string
             */
            getSubmitButtonName: function () {
                var isFrench =
                    document.querySelector("html").lang?.indexOf("fr") === 0;

                if (this.hasCustomerUUID()) {
                    return isFrench ? "Passer la commande" : "Place Order";
                } else {
                    return isFrench
                        ? "Continuez à Sezzle"
                        : "Continue to Sezzle";
                }
            },

            /**
             * Get loader message
             *
             * @returns string
             */
            getLoaderMsg: function () {
                return this.hasCustomerUUID() ? "Placing your order..." : "Redirecting you to Sezzle Checkout...";
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
             *
             * Handle redirection
             */
            handleRedirectAction: function () {
                var self = this;

                self.isPlaceOrderActionAllowed(false);
                self.isRequestPending(true);

                // created order by customer UUID if customer is tokenized
                if (customer.isLoggedIn() && this.hasCustomerUUID()) {
                    this.getCreateSezzleCustomerOrderDeferredObject()
                        .done(
                            function (response) {
                                var jsonResponse = $.parseJSON(response);
                                if (jsonResponse.checkout_url) {
                                    $.mage.redirect(jsonResponse.checkout_url); // if tokenization fails, we create checkout and return the checkout URL
                                    return;
                                }

                                redirectOnSuccessAction.execute(); // successful customer order
                            }
                        ).always(
                        function () {
                            self.isPlaceOrderActionAllowed(true);
                            self.isRequestPending(false);
                        }
                    );
                    return;
                }

                // creates standard checkout
                this.getCreateSezzleCheckoutDeferredObject()
                    .done(
                        function (response) {
                            var jsonResponse = $.parseJSON(response);
                            $.mage.redirect(jsonResponse.checkout_url);
                        }
                    ).always(
                    function () {
                        self.isPlaceOrderActionAllowed(true);
                        self.isRequestPending(false);
                    }
                );
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

                var blocker = this.getCheckoutBlocker();

                if (blocker) {
                    this.messageContainer.addErrorMessage({message: blocker});

                    return;
                }

                if (this.validate() && additionalValidators.validate()) {
                    this.handleRedirectAction();
                }
            }
        });
    }
);
