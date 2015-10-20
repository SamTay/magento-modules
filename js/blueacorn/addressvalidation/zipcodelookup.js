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
        this.fields = {
            postcode: parent.fields.postcode,
            city: parent.fields.city,
            region_id: parent.fields.region_id
        }
        this.lookupInProgress = false;
        this.organizeFormFields();
        this.setupFormObservers();
    },

    /**
     * Move postcode field before city/state if necessary
     */
    organizeFormFields: function() {
        var firstField = this.getFirstField();

        // Move postcode field before city/state
        if (firstField != 'postcode') {
            $(this.fields[firstField]).up('div.field').insert({
                before: $(this.fields.postcode)
            });
        }
    },

    /**
     * Setup observer on postcode field to ajax populate the city/state
     */
    setupFormObservers: function () {
        $(this.fields.postcode).on('keydown', function(event) {
            if ($(this.fields.postcode).value.length >= 5 && !this.lookupInProgress) {
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
            parameters: {zipcode: $(this.fields.postcode).value},
            onSuccess: function(response) {
                if (response.hasOwnProperty(responseJSON)) {
                    this.populateCityState(response.responseJSON);
                }
            },
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
                    $(this.fields[fieldKey]).value = responseJSON[fieldKey];
                }
            }
        });
        this.lookupInProgress = false;
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
