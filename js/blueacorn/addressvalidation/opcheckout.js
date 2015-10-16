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
     * Accepts Onepage Shipping object as parent
     * @param $super
     * @param parent
     */
    initialize: function($super, parent) {
        $super(parent.form);
        this.parent = parent;
    },

    /**
     * Specify field prefix and call parent method to unpack
     * @param $super
     * @param addressJSON
     */
    unpackToParentForm: function($super, addressJSON) {
        $super(addressJSON, 'shipping:');
    }
});

(function(){
    /**
     * Generate some wrapper methods for shipping/billing save actions. $super is the prototype wrap
     * passed down from shipping/billing, while wrapper is the custom wrap
     */
    var wrapperGenerator = function (wrapper) {
        return function ($super) {
            // HALT if ba object doesn't exit
            if (typeof ba === "undefined") {
                console.log('Address Validation module depends on GP...');
                return $super();
            }
            var formValidator = new Validation(this.form);
            if (!formValidator.validate()) {
                return;
            }
            wrapper.call(this, $super);
        }
    }
    var shippingWrapper = wrapperGenerator(function ($super) {
        var notInUS = !($F('shipping:country_id') == 'US'),
            alreadyVerified = $('shipping-address-select') && $F('shipping-address-select') && verifiedAddressJson[$F('shipping-address-select')];

        // Validation only available for US. Previously verified addresses can skip this step
        if (notInUS || alreadyVerified) {
            return $super();
        }

        if (!this.addressValidator) {
            this.addressValidator = new OPAddressValidator(this);
        }
        this.addressValidator.validate($super.bind(this));
    });
    var billingWrapper = wrapperGenerator(function ($super) {
        if (checkout.loadWaiting != false) return;
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
    Event.observe(window, 'load', function() {
        if (typeof Shipping !== "undefined") {
            Shipping.prototype.save = Shipping.prototype.save.wrap(shippingWrapper);
        }
        if (typeof Billing !== "undefined") {
            Billing.prototype.save = Billing.prototype.save.wrap(billingWrapper);
        }
    });
})();
