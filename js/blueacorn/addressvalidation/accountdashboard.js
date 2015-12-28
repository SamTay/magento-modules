/**
 * Account Dashboard - Address Validation
 *
 * @package     BlueAcorn/AddressValidation
 * @version     0.2.0
 * @author      Sam Tay @ Blue Acorn <code@blueacorn.com>
 * @copyright   Copyright © 2015 Blue Acorn, Inc.
 */
var ADAddressValidator = Class.create(AddressValidator, {
    /**
     * Initialize class, override some settings, and instantiate zipcode tool
     * @param $super
     */
    initialize: function($super, parentFormId) {
        $super(parentFormId);
        this.url = '/ba_validation/address/account';
        this.slideTimeout = 10000
        this.fields = {
            street1: 'street_1',
            street2: 'street_2',
            postcode: 'zip',
            city: 'city',
            region_id: 'region_id'
        };
        this.countryId = 'country';
        this.setupObserversIfEnabled();
        if (this.getConfig('blueacorn_addressvalidation/account/city_state')) {
            this.zipcodeLookupTool = new ZipcodeLookupTool(this);
        }
    },

    /**
     * Override abstract toggleInProgress method to do DOM manipulation that is Account Dashboard specific
     *
     * @param $super
     * @param toggle
     */
    toggleInProgress: function($super, toggle) {
        $super(toggle);
        var $button = $(this.parentForm).select('button[type="submit"]').first();
        if (this.requestInProgress) {
            $button.addClassName('disabled request-in-progress');
        } else {
            $button.removeClassName('disabled request-in-progress');
        }
    },

    /**
     * Override so that "submit" action doesn't try to remove form,
     * because submitting post takes longer here, and since the action isn't ajax,
     * there's no reason to change what the page currently looks like.
     */
    onSlideSuccessContinue: null,

    /**
     * Override so that "continue" action doesn't try to remove form,
     * for the same reason as above.
     */
    onSlideErrorContinue: null,

    /**
     * Inject address validator onto form submission
     */
    setupObservers: function() {
        var self = this,
            $form = $(this.parentForm);
        if ($form) {
            $form.observe('submit', function(event) {
                // Check if we can validate current selected country
                if (!self.canValidateCountry()) {
                    return true;
                }
                Event.stop(event);
                // Attach ADAddressValidator
                if (!this.addressValidator) {
                    this.addressValidator = self;
                }
                // Validate address
                this.addressValidator.validate($form.submit.bind($form));
            });
        }
    }
});

Event.observe(window, "load", function() {
    var adAddressValidator = new ADAddressValidator('form-validate');
});