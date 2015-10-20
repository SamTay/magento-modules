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
                this.lookupInProgress = true;
                this.updateCityState();
            }
        }.bind(this));
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
                this.lookupInProgress = false;
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
                    //$(this.fields[fieldKey]).value = responseJSON[fieldKey];
                }
            }
        }, this);
        jQuery(document).trigger('update:all');
    },

    /**
     * Get first relevant input in the form of this.fields key
     * @returns string ('postcode', 'city', or 'region_id')
     */
    getFirstField: function() {
        // Quick getKey method for this.fields
        var getKey = function(object, value) {
            for(var key in object) {
                if (object[key] == value) {
                    return key;
                }
            }
            return null;
        }
        // Determine first relevant input
        var fieldsArray = [],
            indices = [],
            indexMap = {};
        for (var key in this.fields) {
            fieldsArray.push(this.fields[key]);
        }
        $$('input#' + fieldsArray.join(', input#')).each(function(input, index) {
            indexMap[index] = getKey(this.fields, $(input).readAttribute('id'));
            indices.push(index);
        });
        return indexMap[indices.min()];
    }
});
