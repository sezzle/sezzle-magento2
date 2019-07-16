define(
    [
        'jquery',
        'uiComponent',
        'mage/translate',
        'jquery/ui',
        'jquery/validate'
    ],
    function ($, Class, $t) {
        'use strict';

        return Class.extend({

                defaults: {
                    $sezzleMerchantId: null,
                    selector: 'sezzlepay_sezzlepay',
                    $container: null,
                    $form: null,
                },

                /**
                 * Set list of observable attributes
                 * @returns {exports.initObservable}
                 */
                initObservable: function () {
                    var self = this;

                    self.$sezzleConfig = $('#sezzle_config');
                    self.$sezzlePaymentHeader = $('#payment_' + self.getCountry() + '_' + self.selector
                        + '_payment-head');
                    self.$sezzleMerchantId = $('#payment_' + self.getCountry() + '_' + self.selector
                        + '_payment_merchant_id').val();
                    self.$container = $('#sezzle_config');

                    if (self.$sezzleMerchantId) {
                        self.hideSezzleConfig();
                    }
                    else {
                        self.showSezzleConfig();
                    }

                    if (!self.$form) {
                        self.generateSimplePathForm();
                    }

                    self._super();

                    self.initEventHandlers();

                    return self;
                },

                /**
                 * Init event handlers
                 */
                initEventHandlers: function () {
                    var self = this;

                    $('#sezzle-config-skip').click(function () {
                        self.hideSezzleConfig();
                        return false;
                    });
                },

                /**
                 * Sets up dynamic form for capturing popup/form input for simple path setup.
                 */
                generateSimplePathForm: function () {

                    this.$form = new Element('form', {
                        method: 'post',
                        action: this.sezzleUrl,
                        id: 'sezzle_config_form',
                        target: 'config',
                        novalidate: 'novalidate',
                    });

                    this.$container.wrap(this.$form);
                },

                /**
                 * display sezzle simple path config section
                 */
                showSezzleConfig: function () {
                    this.$sezzleConfig.show();
                    if (this.$sezzlePaymentHeader.hasClass('open')) {
                        this.$sezzlePaymentHeader.click();
                    }
                },

                /**
                 * hide sezzle simple path config.
                 */
                hideSezzleConfig: function () {
                    this.$sezzleConfig.hide();
                    if (!this.$sezzlePaymentHeader.hasClass('open')) {
                        this.$sezzlePaymentHeader.click();
                    }
                },

                /**
                 * Get country code
                 * @returns {String}
                 */
                getCountry: function () {
                    return this.co.toLowerCase();
                },
            }
        );
    }
);
