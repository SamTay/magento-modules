//TODO: Ensure the "parent" methodology is extendable to multishipping .. if not, refactor this class to be more general

var AddressValidator = Class.create({
    initialize: function(form) {
        this.parentForm = form;
        this.url = '/ba_validation/ajax'
        this.form = 'validated-address-form'
        this.fields = ['street1', 'street2', 'postcode', 'city', 'region_id'];
    },

    validate: function(callback) {
        this.callback = callback;
        this.getValidatedAddress(this.showAjaxResult.bind(this), this.callback.bind(this));
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
        } else if (response.responseJSON.error) {
            this.showError(response);
        } else {
            this.callback();
        }
    },

    showForm: function(response) {
        var self = this;
        var modal = Dialog.info(response.responseJSON.form, {
            title: "Verify Your Address",
            className: "validated-addresses-modal",
            width: 350,
        });
        $$('#validated-address-form button.btn-submit').first().observe('click', function(ev) {
            var addressId = $$('input:checked[type=radio][name=validated_address]')[0].value;
            self.unpackToParentForm(response.responseJSON.addresses[addressId]);
            self.callback();
            modal.close();
        });
        $$('#validated-address-form button.btn-cancel').first().observe('click', function(ev) {
            modal.close();
        });
    },

    unpackToParentForm: function(addressJSON, fieldPrefix) {
        fieldPrefix = fieldPrefix ? fieldPrefix : "";
        this.fields.each(function(field, index) {
            Form.Element.setValue(fieldPrefix + field, addressJSON[field]);
        });
    },

    showError: function(response) {
        var self = this;
        var modal = Dialog.info(response.responseJSON.error, {
            className: "error-modal",
            width: 350,
        });
        $$('.error-container button.btn-continue').first().observe('click', function(ev) {
            self.callback();
            modal.close();
        });
        $$('.error-container button.btn-cancel').first().observe('click', function(ev) {
            modal.close();
        });
    }
});
