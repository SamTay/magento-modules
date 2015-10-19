/**
 * Account Dashboard - Address Validation
 *
 * @package     BlueAcorn/AddressValidation
 * @version     0.1.0
 * @author      Sam Tay @ Blue Acorn <code@blueacorn.com>
 * @copyright   Copyright © 2015 Blue Acorn, Inc.
 */
var ADAddressValidator = Class.create(AddressValidator, {
    initialize: function($super) {
        $super();
        this.url = '/ba_validation/ajax/account';
        this.fields = ['street_1', 'street_2', 'zip', 'city', 'region_id'];
        this.slideTimeout = 10000
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
     * Injects validator into varien form object, modifies submit button to allow ajax request before submit
     */
    setupObservers: function() {
        if (typeof dataForm !== "undefined") {
            dataForm.form.select('button[type="submit"]').first()
                .writeAttribute('onclick', 'dataForm.submit()')
                .writeAttribute('type', 'button');
            dataForm.submit = dataForm.submit.wrap(this.wrapperGenerator(function ($super) {
                // Validation only available for US addresses
                var notInUS = !($F('country') == 'US');
                if (notInUS) {
                    return $super();
                }
                // Attach ADAddressValidator
                if (!this.addressValidator) {
                    this.addressValidator = adAddressValidator.attach(this.form);
                }
                // Validate address
                this.addressValidator.validate($super.bind(this));
            }));
        }
    }
});

Event.observe(window, "load", function() {
    var adAddressValidator = new ADAddressValidator();
});