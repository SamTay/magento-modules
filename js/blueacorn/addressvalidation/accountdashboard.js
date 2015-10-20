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
     * Initialize class and override some settings
     * @param $super
     */
    initialize: function($super) {
        $super();
        this.url = '/ba_validation/ajax/account';
        this.slideTimeout = 10000
        this.fields = {
            street1: 'street_1',
            street2: 'street_2',
            postcode: 'zip',
            city: 'city',
            region_id: 'region_id'
        };
    },

    /**
     * Override so that "submit" action doesn't try to remove form,
     * because submitting post takes longer here, and since the action isn't ajax,
     * there's no reason to change what the page currently looks like.
     */
    bindSlideSuccessObservers: function() {
        this.bindSuccessObservers(null, function() {
            $(this.form).remove();
            new Effect.SlideDown(this.parentForm);
        }.bind(this));
    },

    /**
     * Override so that "continue" action doesn't try to remove form,
     * for the same reason as above.
     */
    bindSlideErrorObservers: function() {
        this.bindErrorObservers(null, function() {
            $$('.error-container').first().remove();
            new Effect.SlideDown(this.parentForm);
        }.bind(this));
    },

    /**
     */
    setupObservers: function() {
        var self = this,
            $form = $('form-validate');
        if ($form) {
            $form.observe('submit', function(event) {
                // Validation only available for US addresses
                var notInUS = !($F('country') == 'US');
                if (notInUS) {
                    return true;
                }
                Event.stop(event);
                // Attach ADAddressValidator
                if (!this.addressValidator) {
                    this.addressValidator = self.attach(this);
                }
                // Validate address
                this.addressValidator.validate($form.submit.bind($form));
            });
        }
    }
});

Event.observe(window, "load", function() {
    var adAddressValidator = new ADAddressValidator();
});