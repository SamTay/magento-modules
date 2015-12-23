/**
 * Address Validation - Abstract Class - extend for new integrations
 * When extending, must define
 * this.url
 * this.fields
 * this.setupObservers
 *
 * @package     BlueAcorn/AddressValidation
 * @version     0.2.0
 * @author      Sam Tay @ Blue Acorn <code@blueacorn.com>
 * @copyright   Copyright © 2015 Blue Acorn, Inc.
 */
var AddressValidator = Class.create({
    /**
     * Inititalize class
     * @param form
     */
    initialize: function(parentFormId) {
        this.form = 'validated-address-form';
        this.parentForm = parentFormId;
        this.modalWidth = mageConfig['blueacorn_addressvalidation/design/modal_width'];
        this.domesticEnabled = !!mageConfig['blueacorn_addressvalidation/general/enabled_domestic_apis'];
        this.internationalEnabled = !!mageConfig['blueacorn_addressvalidation/general/enabled_international_apis'];
        this.slideTimeout = 3800;
        /**
         * Override this.url in specific extended integrations
         * See accountdashboard.js for examples
         * @type {string}
         */
        this.url = '/ba_validation/address/checkout';
        /**
         * Override this.fields and this.countryId in specific integrations to match parent form input IDs
         * See accountdashboard.js for examples
         * @type {{street1: string, street2: string, postcode: string, city: string, region_id: string}}
         */
        this.fields = {
            street1: 'street1',
            street2: 'street2',
            postcode: 'postcode',
            city: 'city',
            region_id: 'region_id'
        };
        this.countryId = 'country';
    },

    /**
     * Validate address found in this.parentForm and then continueAddressSave() when finished
     * @param continueAddressSave
     */
    validate: function(continueAddressSave) {
        this.continueAddressSave = continueAddressSave;
        this.getValidatedAddress(this.showAjaxResult.bind(this), this.continueAddressSave);
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
            this.continueAddressSave();
        }
    },

    /**
     * Show validated addresses form if APIs were able to verify/suggest
     */
    showForm: function() {
        if (this.responseJSON.is_modal) {
            this.openModal(
                this.responseJSON.form,
                "validated-addresses-modal",
                this.bindModalSuccessObservers.bind(this)
            );
        } else {
            this.slideContent(this.responseJSON.form, this.bindSlideSuccessObservers);
        }
    },

    /**
     * Show error message if it exists on response (this happens when APIs cannot verify)
     */
    showError: function() {
        if (this.responseJSON.is_modal) {
            this.openModal(
                this.responseJSON.error,
                "error-modal",
                this.bindModalErrorObservers.bind(this)
            );
        } else {
            this.slideContent(this.responseJSON.error, this.bindSlideErrorObservers);
        }
    },

    /**
     * Generic open modal method for any content
     * TODO: Check for jQuery otherwise prototype window
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
     * Slide action to show form/error content instead of modal
     * @param content
     */
    slideContent: function(content, callback) {
        // Remove form/error content if already exists
        var $form = $(this.form),
            $error = $$('.error-container');
        if ($form) {
            $form.remove();
        }
        if ($error.length) {
            $error.first().remove();
        }

        // Insert new content and call callback
        $(this.parentForm).insert({
            after: content
        });
        new Effect.SlideUp(this.parentForm);
        callback.call(this);
    },

    /**
     * Bind observers for form content, pass callbacks for submit/cancel events
     * @param continueCb
     * @param cancelCb
     */
    bindSuccessObservers: function(continueCb, cancelCb) {
        // Handle submit action
        $(this.form).querySelector('.btn-submit').observe('click', function(event) {
            Event.stop(event);
            var addressId = $$('input:checked[type=radio][name=validated_address]')[0].value;
            if (addressId != 'original') {
                this.unpackToParentForm(this.responseJSON.addresses[addressId]);
            }
            if (typeof continueCb === "function") continueCb.call(this);
            this.continueAddressSave();
        }.bind(this));

        // Handle go back action
        $(this.form).select('.go-back').each(function(element) {
            element.observe('click', function(event) {
                Event.stop(event);
                if (typeof cancelCb === "function") cancelCb.call(this);
            }.bind(this));
        }.bind(this));
    },

    /**
     * Bind observers for error content, pass callbacks for continue/cancel events
     * @param continueCb
     * @param cancelCb
     */
    bindErrorObservers: function(continueCb, cancelCb) {
        $$('.error-container button.btn-continue').first().observe('click', function(event) {
            Event.stop(event);
            if (typeof continueCb === "function") continueCb.call(this);
            this.continueAddressSave();
        }.bind(this));
        $$('.error-container .go-back').each(function(element) {
            element.observe('click', function(event) {
                Event.stop(event);
                if (typeof cancelCb === "function") cancelCb.call(this);
            }.bind(this));
        }.bind(this));
    },

    /**
     * Bind modal events for validated address form content
     */
    bindModalSuccessObservers: function(){
        this.bindSuccessObservers(jQuery.fancybox.close, jQuery.fancybox.close);
    },

    /**
     * Bind slide events for validated address form content
     */
    bindSlideSuccessObservers: function() {
        this.bindSuccessObservers(this.onSlideSuccessContinue, function() {
            $(this.form).remove();
            new Effect.SlideDown(this.parentForm);
        });
    },

    /**
     * Bind modal events for error message content
     */
    bindModalErrorObservers: function() {
        this.bindErrorObservers(jQuery.fancybox.close, jQuery.fancybox.close);
    },

    /**
     * Bind slide events for error message content
     */
    bindSlideErrorObservers: function() {
        this.bindErrorObservers(this.onSlideErrorContinue, function() {
            $$('.error-container').first().remove();
            new Effect.SlideDown(this.parentForm);
        });
    },

    /**
     * Note: These timeouts are default, but they are worst case scenario. Ideally,
     * override this in specific areas and inject this logic without timeout via
     * wraps or listening for events
     */
    onSlideSuccessContinue: function() {
        setTimeout(function() {
            $(this.form).remove();
            $(this.parentForm).show();
        }.bind(this), this.slideTimeout);
    },

    /**
     * Note: These timeouts are default, but they are worst case scenario. Ideally,
     * override this in specific areas and inject this logic without timeout via
     * wraps or listening for events
     */
    onSlideErrorContinue: function() {
        setTimeout(function() {
            $$('.error-container').first().remove();
            $(this.parentForm).show();
        }.bind(this), this.slideTimeout);
    },

    /**
     * Fill parent address form with values from addressJSON. This is used when a user
     * selects an address from the validated address form
     * @param addressJSON
     */
    unpackToParentForm: function(addressJSON) {
        for(var key in this.fields) {
            Form.Element.setValue(this.fields[key], addressJSON[key]);
        }
    },

    /**
     * Generate some wrapper methods to reduce code duplication . $super is the prototype wrap
     * passed down from parent (like shipping/billing/account-form), while wrapper is the custom wrap
     */
    wrapperGenerator: function(wrapper) {
        return function ($super) {
            // HALT if ba object doesn't exit
            if (typeof ba === "undefined") {
                console.log('Address Validation module depends on GP...');
                return $super();
            }
            // Varien form validation comes first
            var formValidator = new Validation(this.form);
            if (!formValidator.validate()) {
                return;
            }
            wrapper.call(this, $super);
        }
    },

    /**
     * Check if the appropriate APIs are enabled for this request
     * @returns {boolean|*}
     */
    canValidateCountry: function() {
        return (this.domesticEnabled && $F(this.countryId) == 'US')
            || (this.internationalEnabled && $F(this.countryId) != 'US');
    },

    /**
     * Override this.setupObservers in specific integrations to attach "this" to parent forms
     */
    setupObserversIfEnabled: function() {
        if (this.domesticEnabled || this.internationalEnabled) {
            this.setupObservers();
        }
    },

    /**
     * Override in extended classes to handle injection
     */
    setupObservers: function() {}
});
