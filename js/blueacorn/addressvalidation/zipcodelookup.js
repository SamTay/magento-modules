/**
 * Zipcode lookup tool - instantiated object belongs to address validator object
 *
 * @package     BlueAcorn/AddressValidation
 * @version     0.1.0
 * @author      Sam Tay @ Blue Acorn <code@blueacorn.com>
 * @copyright   Copyright © 2015 Blue Acorn, Inc.
 */
var ZipcodeLookupTool = Class.create({
    /**
     * Initialize zipcode lookup tool by passing parent addressvalidator object
     * @param parent
     */
    initialize: function(parent) {
        this.url = '/ba_validation/zipcode/lookup';
        this.form = parent.form;
        this.fields = parent.fields;
        this.fields.country = parent.countryId;
        this.lookupInProgress = false;
        this.organizeFormFields();
        this.setupFormObservers();
    },

    /**
     * Move postcode field before city/state if necessary
     */
    organizeFormFields: function() {
        // Move postcode field before city/state
        $(this.fields.city).up('div.field').insert({
            before: $(this.fields.postcode).up('div.field')
        });
    },

    /**
     * Setup observer on postcode field to ajax populate the city/state
     */
    setupFormObservers: function () {
        $(this.fields.postcode).on('keyup', function(event) {
            if ($(this.fields.postcode).value.length >= 5
                && !this.lookupInProgress
                && $F(this.fields.country) == 'US'
            ) {
                this.toggleInProgress(true);
                this.updateCityState();
            }
        }.bind(this));
    },

    /**
     * Set this.lookInProgress boolean and apply/remove any in-progress styles
     * @param inProgress
     */
    toggleInProgress: function(inProgress) {
        this.lookupInProgress = inProgress;
        if (inProgress) {
            ['city', 'region_id'].forEach(function(fieldKey) {
                $(this.fields[fieldKey]).up('div.field').addClassName('zipcode-lookup-progress');
            }, this);
        } else {
            ['city', 'region_id'].forEach(function(fieldKey) {
                $(this.fields[fieldKey]).up('div.field').removeClassName('zipcode-lookup-progress');
            }, this);
        }
    },

    /**
     * Ajax request to update city state field given current postcode value
     */
    updateCityState: function() {
        new Ajax.Request(this.url, {
            parameters: {postcode: $(this.fields.postcode).value},
            onSuccess: function(response) {
                if (response.hasOwnProperty('responseJSON')) {
                    this.populateCityState(response.responseJSON);
                }
            }.bind(this),
            onComplete: function() {
                this.toggleInProgress(false);
            }.bind(this)
        });
    },

    /**
     * If city/region_id keys exist in responseJSON, populate the appropriate form fields
     * @param responseJSON
     */
    populateCityState: function(responseJSON) {
        ['city', 'region_id'].forEach(function(fieldKey) {
            var currentValue = $(this.fields[fieldKey]);
            if (responseJSON.hasOwnProperty(fieldKey)) {
                if (!currentValue || responseJSON[fieldKey].indexOf(currentValue)) {
                    Form.Element.setValue(this.fields[fieldKey], responseJSON[fieldKey]);
                }
            }
        }, this);
        jQuery(document).trigger('update:all');
    }
});
