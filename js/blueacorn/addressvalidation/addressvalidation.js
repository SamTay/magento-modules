var AddressValidator = Class.create({
    initialize: function(form) {
        this.parentForm = form;
        this.url = '/ba_validation/ajax/checkout';
        this.form = 'validated-address-form';
        this.fields = ['street1', 'street2', 'postcode', 'city', 'region_id'];
        this.modalWidth = mageConfig['blueacorn_addressvalidation/design/modal_width'];
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
        this.openModal(
            response.responseJSON.form,
            "validated-addresses-modal",
            this.bindModalSuccessObservers
        );
    },

    openModal: function(content, wrapClass, afterShow){
        var self = this;
        jQuery.fancybox.open({
            content  : content,
            wrapCSS  : wrapClass,
            minWidth : self.modalWidth,
            afterShow: function(){
                if(typeof afterShow === "function"){
                    afterShow();
                }
            }
        });
    },

    bindModalSuccessObservers: function(){
        var self = this;

        $$('#validated-address-form button.btn-submit').first().observe('click', function(event) {
            Event.stop(event);
            var addressId = $$('input:checked[type=radio][name=validated_address]')[0].value;
            if (addressId != 'original') {
                self.unpackToParentForm(response.responseJSON.addresses[addressId]);
            }
            jQuery.fancybox.close();
            self.callback();
        });

        $$('#validated-address-form .go-back').first().observe('click', function(event) {
            Event.stop(event);
            jQuery.fancybox.close();
        });
    },

    bindModalErrorObservers: function(){
        var self = this;

        $$('.error-container button.btn-continue').first().observe('click', function(event) {
            Event.stop(event);
            jQuery.fancybox.close();
            self.callback();
        });
        $$('.error-container button.btn-cancel').first().observe('click', function(event) {
            Event.stop(event);
            jQuery.fancybox.close();
        });
    },

    unpackToParentForm: function(addressJSON, fieldPrefix) {
        fieldPrefix = fieldPrefix ? fieldPrefix : "";
        this.fields.each(function(field, index) {
            Form.Element.setValue(fieldPrefix + field, addressJSON[field]);
        });
    },

    showError: function(response) {
        // Open Error Modal
        this.openModal(
            response.responseJSON.error,
            "error-modal",
            this.bindModalErrorObservers
        );
    }
});
