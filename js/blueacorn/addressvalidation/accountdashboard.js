/**
 * Account Dashboard - Address Validation
 *
 * @package     BlueAcorn/AddressValidation
 * @version     0.1.0
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
        // Need explicit check against true to convert to integer/boolean (because "0" is truthy as string)
        if (mageConfig['blueacorn_addressvalidation/account/city_state'] == true) {
            this.zipcodeLookupTool = new ZipcodeLookupTool(this);
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
            $form = $(this.form);
        if ($form) {
            $form.observe('submit', function(event) {
                // Validation only available for US addresses
                var notInUS = ($F(self.countryId) != 'US');
                if (notInUS) {
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