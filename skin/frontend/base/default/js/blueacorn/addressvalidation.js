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
