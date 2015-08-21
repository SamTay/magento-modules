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
        $submit = $(this.form).querySelector('.btn-submit');
        $submit.observe('click', function(event) {
            Event.stop(event);
            var addressId = $$('input:checked[type=radio][name=validated_address]')[0].value;
            if (addressId != 'original') {
                this.unpackToParentForm(response.responseJSON.addresses[addressId]);
            }
            this.callback();
            setTimeout(function() {
                $(this.form).remove();
                $(this.parentForm).show();
            }.bind(this), 2000);
        }.bind(this));
    },

    showError: function($super, response) {
        if (response.responseJSON.is_modal) {
            return $super(response);
        }
        this.slideStepContent(response.responseJSON.error);

        //Handle go back action
        $$('.go-back').each(function(element) {
           element.observe('click', function(event) {
               Event.stop(event);
               $$('div.error-container').first().remove();
               new Effect.SlideDown(this.parentForm);
           }.bind(this));
        }.bind(this));

        //Handle continue action
        $$('.error-container button.btn-continue').first().observe('click', function(event) {
            Event.stop(event);
            this.callback();
            setTimeout(function() {
                $$('div.error-container').first().remove();
                $(this.parentForm).show();
            }.bind(this), 2000);
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

            var notInUS = !($F('shipping:country_id') == 'US'),
                alreadyVerified = $('shipping-address-select') && $F('shipping-address-select') && verifiedAddressJson[$F('shipping-address-select')];

            if (notInUS || alreadyVerified) {
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
                    // Skip if already verified
                    if ($F('billing-address-select') && verifiedAddressJson[$F('billing-address-select')]) {
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
            }
        });
    }
});
