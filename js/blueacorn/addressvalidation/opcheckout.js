/**
 * Onepage Checkout - Address Validation
 *
 * @package     BlueAcorn/AddressValidation
 * @version     0.1.0
 * @author      Sam Tay @ Blue Acorn <code@blueacorn.com>
 * @copyright   Copyright © 2015 Blue Acorn, Inc.
 */
var OPAddressValidator = Class.create(AddressValidator, {
    /**
     * Initialize class and override field prefix
     * @param $super
     */
    initialize: function($super) {
        $super();
        this.fieldPrefix = 'shipping:';
    },

    /**
     * Show form as normal, then define a wrapper for shipping.onComplete
     * @param $super
     */
    showAjaxResult: function($super, response) {
        $super(response);
        this.wrapShippingOnComplete();
    },

    /**
     * Fill parent address form with values from addressJSON
     * Unset the possibly selected customer address ID from dropdown
     * @param addressJSON
     * @param fieldPrefix
     */
    unpackToParentForm: function($super, addressJSON) {
        $super(addressJSON);
        shipping.resetSelectedAddress();
        this.newAddress = true;
    },

    /**
     * Wrap shipping.onComplete to clean up some address validator tasks
     */
    wrapShippingOnComplete: function() {
        if (this.shippingOnCompleteWrapped) {
            return;
        }
        var addressValidator = this;
        shipping.onComplete = shipping.onComplete.wrap(function($super) {
            $super();
            // Show full address form (instead of shipping-address-select) if user submitted validated address
            if (addressValidator.newAddress) {
                this.newAddress(true);
                addressValidator.newAddress = false;
            }

            // Remove form/error content
            if ($(addressValidator.form)) {
                $(addressValidator.form).remove();
            }
            if ($$('.error-container').length > 0) {
                $$('.error-container').first().remove();
            }

            // Show shipping form by default
            $(this.form).show();
        }.bind(shipping));
        this.shippingOnCompleteWrapped = true;
    },

    /**
     * Override timeout as this gets taken care of in shiping.onComplete
     */
    onSlideSuccessContinue: null,

    /**
     * Override timeout as this gets taken care of in shiping.onComplete
     */
    onSlideErrorContinue: null,

    /**
     * Inject address validator into wrapped methods of op checkout objects
     */
    setupObservers: function() {
        var self = this;
        var shippingWrapper = this.wrapperGenerator(function ($super) {
            var notInUS = !($F('shipping:country_id') == 'US'),
                alreadyVerified = $('shipping-address-select') && $F('shipping-address-select') && verifiedAddressJson[$F('shipping-address-select')];

            // Validation only available for US. Previously verified addresses can skip this step
            if (notInUS || alreadyVerified) {
                return $super();
            }

            if (!this.addressValidator) {
                this.addressValidator = self.attach(this.form);
            }
            this.addressValidator.validate($super.bind(this));
        });
        var billingWrapper = this.wrapperGenerator(function ($super) {
            if (checkout.loadWaiting) return;
            /**
             * If we are using billing for shipping, uncheck billing for shipping option so that this doesn't
             * get saved in the billingSaveAction request. After saving billing address via $super, sync the
             * billing address to the saving form, and then shipping.save() to hook into the wrapper defined above
             */
            var useForShipping = $('billing:use_for_shipping_yes').checked;
            if (useForShipping) {
                // Skip if already verified
                if ($('billing-address-select') && $F('billing-address-select') && verifiedAddressJson[$F('billing-address-select')]) {
                    return $super();
                }
                $('billing:use_for_shipping_yes').checked = false;
            }

            $super();

            if (useForShipping) {
                $('billing:use_for_shipping_yes').checked = true;
                this.setUseForShipping(true);
                shipping.syncWithBilling();
                shipping.save();
            }
        });
        if (typeof Shipping !== "undefined") {
            Shipping.prototype.save = Shipping.prototype.save.wrap(shippingWrapper);
        }
        if (typeof Billing !== "undefined") {
            Billing.prototype.save = Billing.prototype.save.wrap(billingWrapper);
        }
    }
});

Event.observe(window, "load", function() {
    var opAddressValidator = new OPAddressValidator();
});
