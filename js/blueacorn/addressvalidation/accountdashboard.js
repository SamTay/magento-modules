/**
 * Account Dashboard - Address Validation
 *
 * @package     BlueAcorn/AddressValidation
 * @version     0.1.0
 * @author      Sam Tay @ Blue Acorn <code@blueacorn.com>
 * @copyright   Copyright © 2015 Blue Acorn, Inc.
 */
var ADAddressValidator = Class.create(AddressValidator, {
    initialize: function($super, form) {
        $super(form);
        this.url = '/ba_validation/ajax/account';
        this.fields = ['street_1', 'street_2', 'zip', 'city', 'region_id'];
    },
});

Event.observe(window, 'load', function () {
    if (typeof dataForm !== "undefined") {
        dataForm.form.select('button[type="submit"]').first()
            .writeAttribute('onclick', 'dataForm.submit()')
            .writeAttribute('type', 'button');
        dataForm.submit = dataForm.submit.wrap(function ($super) {
            // HALT if ba object doesn't exit
            if(typeof ba === "undefined"){
                console.log('Address Validation module depends on GP...');
                return $super();
            }

            // Validation only available for US addresses
            var notInUS = !($F('country') == 'US');
            if (notInUS) {
                return $super();
            }

            // Attach ADAddressValidator
            if (!this.addressValidator) {
                this.addressValidator = new ADAddressValidator(this.form);
            }

            // Varien validator comes first
            if (this.validator && this.validator.validate()) {
                this.addressValidator.validate($super.bind(this));
            }
        });
    }
});
