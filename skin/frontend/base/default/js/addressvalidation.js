//TODO: Ensure the "parent" methodology is extendable to multishipping .. if not, refactor this class to be more general

var AddressValidator = Class.create({
    initialize: function(form) {
        this.parentForm = form;
        this.url = '/ba_validation/ajax'
        this.form = 'validated-address-form'
        this.fields = ['street', 'postcode', 'city', 'region_id'];
    },

    validate: function(callback) {
        this.callback = callback;
        this.getValidatedAddress(this.showAjaxResult, this.callback);
    },

    getValidatedAddress: function(success, failure) {
        new Ajax.Request(this.url, {
            parameters: Form.serialize(this.parentForm),
            onSuccess: success,
            onFailure: failure
        });
    },

    showAjaxResult: function(response) {
        if (response.responseJSON.form) {
            this.showForm(response);
        } else if (response.responseJSON.errors) {
            this.showError(response);
        }
    },

    showForm: function(response) {
        //TODO: Make default AddressValidator.showForm the modal pop up
    },

    showError: function(response) {
        // Default modal
    }
});

//TODO: Put the rest of this file in its own file, only to be included in checkout_onepage handle
var OPAddressValidator = Class.create(AddressValidator, {
    initialize: function($super, parent) {
        this.parent = parent;
        $super(parent.form);
    },

    showForm: function($super, response) {
        //TODO: Only override if set in system config; otherwise just call $super
        this.slideStepContent(response.responseJSON.form);

        // Handle go back action
        $(this.form).select('.go-back').each(function(element, index) {
            element.observe('click', function(event) {
                Event.stop(event);
                $(this.form).remove();
                Effect.slideDown(this.parentForm);
            }.bind(this));
        }.bind(this));

        // Handle submitting validated address
        $(this.form).observe('submit', function(event) {
            Event.stop(event);
            var addressId = $F('validated_address');
            this.unpackToParentForm(response.responseJSON.address[addressId]);
            this.callback();
        }.bind(this));
    },

    slideStepContent: function(content) {
        $(this.parentForm).insert({
            after: content
        });
        Effect.slideUp(this.parentForm);
    },

    unpackToParentForm: function(addressJSON) {
        this.fields.each(function(field, index) {
            Form.Element.setValue('shipping:' + field, addressJSON[field])
        });
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
});

