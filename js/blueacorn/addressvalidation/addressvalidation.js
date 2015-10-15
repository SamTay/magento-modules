/**
 * Address Validation - Abstract Class - extend for new integrations
 * When extending, must define
 * this.url
 * this.fields
 *
 * @package     BlueAcorn/AddressValidation
 * @version     0.1.0
 * @author      Sam Tay @ Blue Acorn <code@blueacorn.com>
 * @copyright   Copyright © 2015 Blue Acorn, Inc.
 */
var AddressValidator = Class.create({
    /**
     * Inititalize class with parent form element
     * @param form
     */
    initialize: function(form) {
        this.parentForm = form;
        this.form = 'validated-address-form';
        this.modalWidth = mageConfig['blueacorn_addressvalidation/design/modal_width'];
        /**
         * Override this.url in specific extended integrations
         * See accountdashboard.js for examples
         * @type {string}
         */
        this.url = '/ba_validation/ajax/checkout';
        /**
         * Override this.fields in specific integrations to match parent form input IDs
         * See accountdashboard.js for examples
         * @type {string[]}
         */
        this.fields = ['street1', 'street2', 'postcode', 'city', 'region_id'];
    },

    /**
     * Validate address found in this.parentForm and then callback() when finished
     * @param callback
     */
    validate: function(callback) {
        this.callback = callback;
        this.getValidatedAddress(this.showAjaxResult.bind(this), this.callback.bind(this));
    },

    /**
     * Perform ajax request, bind success/failure callbacks
     * @param success
     * @param failure
     */
    getValidatedAddress: function(success, failure) {
        new Ajax.Request(this.url, {
            parameters: Form.serialize(this.parentForm),
            onSuccess: success,
            onFailure: failure
        });
    },

    /**
     * Show result from a successful (http code 200) ajax request
     * @param response
     */
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

    /**
     * Show validated addresses form if APIs were able to verify/suggest
     */
    showForm: function() {
        this.openModal(
            this.responseJSON.form,
            "validated-addresses-modal",
            this.bindModalSuccessObservers.bind(this)
        );
    },

    /**
     * Show error message if it exists on response (this happens when APIs cannot verify)
     */
    showError: function() {
        // Open Error Modal
        this.openModal(
            this.responseJSON.error,
            "error-modal",
            this.bindModalErrorObservers.bind(this)
        );
    },

    /**
     * Generic open modal method for any content
     * @param content
     * @param wrapClass
     * @param afterShow
     */
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

    /**
     * Bind modal events for validated address form content
     */
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

    /**
     * Bind modal events for error message content
     */
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

    /**
     * Fill parent address form with values from addressJSON. This is used when a user
     * selects an address from the validated address form
     * @param addressJSON
     * @param fieldPrefix
     */
    unpackToParentForm: function(addressJSON, fieldPrefix) {
        fieldPrefix = fieldPrefix ? fieldPrefix : "";
        this.fields.each(function(field, index) {
            Form.Element.setValue(fieldPrefix + field, addressJSON[field]);
        });
    }
});

