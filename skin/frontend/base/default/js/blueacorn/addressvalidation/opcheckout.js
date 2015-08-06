var OPAddressValidator = Class.create(AddressValidator, {
    initialize: function($super, parent) {
        this.parent = parent;
        $super(parent.form);
    },

    showForm: function($super, response) {
        if (response.responseJSON.is_modal) {
            return $super(response);
        }
        this.slideStepContent(response.responseJSON.form);

        // Handle go back action
        $(this.form).select('.go-back').each(function(element, index) {
            element.observe('click', function(event) {
                Event.stop(event);
                $(this.form).remove();
                new Effect.SlideDown(this.parentForm);
            }.bind(this));
        }.bind(this));

        // Handle submitting validated address
        $(this.form).observe('submit', function(event) {
            Event.stop(event);
            var addressId = $$('input:checked[type=radio][name=validated_address]')[0].value;
            if (addressId != 'original') {
                this.unpackToParentForm(response.responseJSON.addresses[addressId]);
            }
            this.callback();
        }.bind(this));
    },

    slideStepContent: function(content) {
        $(this.parentForm).insert({
            after: content
        });
        new Effect.SlideUp(this.parentForm);
    },

    unpackToParentForm: function($super, addressJSON) {
        $super(addressJSON, 'shipping:');
    }

});

Event.observe(window, 'load', function () {
    if (typeof Shipping !== "undefined") {
        Shipping.prototype.save = Shipping.prototype.save.wrap(function ($super) {
            // Only validate US addresses
            if (!($F('shipping:country_id') == 'US')) {
                return $super();
            }

            var formValidator = new Validation(this.form);
            if (!formValidator.validate()) {
                return;
            }

            if (!this.addressValidator) {
                this.addressValidator = new OPAddressValidator(this);
            }

            this.addressValidator.validate($super.bind(this));
        });
    }

    if (typeof Billing !== "undefined") {
        Billing.prototype.save = Billing.prototype.save.wrap(function($super) {
            if (checkout.loadWaiting) return;
            var validator = new Validation(this.form);
            if (validator.validate()) {

                var useForShipping = $('billing:use_for_shipping_yes').checked;
                if (useForShipping) {
                    $('billing:use_for_shipping_yes').checked = false;
                }

                $super();

                if (useForShipping) {
                    this.setUseForShipping(true);
                    shipping.syncWithBilling();
                    shipping.save();
                }
            }
        });
    }
});
