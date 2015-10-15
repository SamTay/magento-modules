/**
 * Address Validation - Base Class
 *
 * @package     BlueAcorn/AddressValidation
 * @version     0.1.0
 * @author      Sam Tay @ Blue Acorn <code@blueacorn.com>
 * @copyright   Copyright © 2015 Blue Acorn, Inc.
 */
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
        this.responseJSON = response.responseJSON;
        if (this.responseJSON.form) {
            this.showForm();
        } else if (this.responseJSON.error) {
            this.showError();
        } else {
            this.callback();
        }
    },

    showForm: function() {
        this.openModal(
            this.responseJSON.form,
            "validated-addresses-modal",
            this.bindModalSuccessObservers.bind(this)
        );
    },

    openModal: function(content, wrapClass, afterShow){
        var self = this;
        if (!jQuery.fancybox) {
            console.log('Modals require fancybox to be enabled in System Configuration > Javascript Plugins.');
            return;
        }
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
                self.unpackToParentForm(self.responseJSON.addresses[addressId]);
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

    showError: function() {
        // Open Error Modal
        this.openModal(
            this.responseJSON.error,
            "error-modal",
            this.bindModalErrorObservers.bind(this)
        );
    }
});
