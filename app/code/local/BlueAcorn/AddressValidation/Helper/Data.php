<?php
/**
 * @package     BlueAcorn\AddressValidation
 * @version     0.1.0
 * @author      Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2015 Blue Acorn, Inc.
 */
use BlueAcorn_AddressValidation_Helper_Constants as AddressField;
class BlueAcorn_AddressValidation_Helper_Data extends Mage_Core_Helper_Abstract
{
    const CONFIG_PATH = 'blueacorn_addressvalidation';
    const LOG = 'address_validation.log';

    /**
     * Default system config group
     * @var string
     */
    protected $_defaultGroup = 'general';

    /**
     * Holds system config values
     * @var array
     */
    protected $_config = array();

    /**
     * Address fields that uniquely define an address - used in comparing
     * equivalence of two addresses
     * @var array
     */
    protected $_uniqueAddressFields = array(
        AddressField::STREET_LINE_1,
        AddressField::STREET_LINE_2,
        AddressField::POSTCODE,
    );

    /**
     * My signature getConfig helper method
     *
     * @param bool $field
     * @param bool $group
     * @return array|mixed
     */
    public function getConfig($field = false, $group = false)
    {
        if (empty($this->_config)) {
            $this->_config = Mage::getStoreConfig(self::CONFIG_PATH);
        }
        // If called with no arguments, give full 2d array
        if (!$field && !$group) {
            return $this->_config;
        }
        $group = $group ?: $this->_defaultGroup;
        if (!array_key_exists($group, $this->_config)) {
            return null;
        }
        // If you want a field
        if ($field && array_key_exists($field, $this->_config[$group])) {
            return $this->_config[$group][$field];
        }
        // If you only want group
        if (!$field && $group) {
            return $this->_config[$group];
        }
        return null;
    }

    /**
     * Logging method for exceptions or debug mode
     *
     * @param $message
     * @param null $code
     * @param null $api
     */
    public function log($message, $code = null, $api = null)
    {
        if ($code) {
            $message = "$code: $message";
        }
        if ($api) {
            $message = strtoupper($api) . ": $message";
        }
        Mage::log($message, null, self::LOG, true);
    }

    /**
     * Wrapper for the log method that checks if debugging is enabled
     *
     * @param $message
     * @param null $code
     * @param null $api
     */
    public function debug($message, $code = null, $api = null)
    {
        if ($this->isDebugMode()) {
            $this->log($message, $code, $api);
        }
    }

    /**
     * Accepts region ID and returns region code (useful for domestic apis)
     * For example '54' => 'SC' for South Carolina in US
     * or '236' => '54' for Meurthe-et-Moselle in FR
     *
     * @param $regionId
     * @return string
     */
    public function getState($regionId)
    {
        return Mage::getModel('directory/region')->getCollection()
                ->addFieldToSelect('code')
                ->addFieldToFilter('main_table.region_id', $regionId)
                ->getFirstItem()
                ->getCode();
    }

    /**
     * Get region name instead of code
     * For example '54' => 'South Carolina'
     * or '236' => 'Meurthe-et-Moselle')
     *
     * @param $regionId
     * @return string
     */
    public function getRegionName($regionId)
    {
        return Mage::getModel('directory/region')->getCollection()
            ->addFieldToFilter('main_table.region_id', $regionId)
            ->getFirstItem()
            ->getName(); // Mage sets name based on locale on load, no need to add field to select
    }

    /**
     * Get country name (in locale en_US) by country_id
     * For example 'FR' => 'France'
     *
     * @param $countryId
     * @return array
     */
    public function getCountryName($countryId)
    {
        return Mage::getModel('core/locale', 'en_US')->getCountryTranslation($countryId);
    }

    /**
     * Get country ID from country name (where country name is in en_US)
     * I agree, this is terrible, but my hands are tied.
     *
     * @param $countryName
     * @return int|null|string
     */
    public function getCountryId($countryName)
    {
        $countryName = ucwords(strtolower($countryName));
        $countryList = Mage::getModel('core/locale', 'en_US')->getCountryTranslationList();
        foreach($countryList as $id => $name) {
            if ($name == $countryName) {
                return $id;
            }
        }
        return null;
    }

    /**
     * Accepts region code or name and returns region ID
     * For example ('SC', false, 'US') => '54'
     * or ('Meurthe-et-Moselle', true, 'FR') => '236'
     *
     * @param $code
     * @return mixed
     */
    public function getRegionId($code, $byName = false, $countryId = 'US')
    {
        return Mage::getModel('directory/region')->getCollection()
            ->addFieldToSelect(array('code', 'region_id'))
            ->addFieldToFilter('main_table.country_id', $countryId)
            ->addFieldToFilter($byName ? 'name' : 'main_table.code', $code)
            ->getFirstItem()
            ->getRegionId();
    }

    /**
     * Test if two addresses are equivalent (strtoupper values of $fieldsToCompare)
     *
     * @param null|array $address1
     * @param null|array $address2
     * @param array $fieldsToCompare
     * @return bool
     */
    public function compareAddresses($address1 = null, $address2 = null, array $fieldsToCompare = array(), $strict = false)
    {
        if (empty($fieldsToCompare)) {
            $fieldsToCompare = $this->_uniqueAddressFields;
        }
        // Ensure both address arguments are arrays
        foreach (array('1', '2') as $suffix) {
            $input = 'address' . $suffix;
            $$input = (!is_null($$input)) ? $$input : array();
        }

        // Check that addresses are equivalent based on $fieldsToCompare
        foreach ($fieldsToCompare as $field) {
            if (array_key_exists($field, $address1) xor array_key_exists($field, $address2)) {
                return false;
            }
            if (array_key_exists($field, $address1)) {
                if ($strict && $address1[$field] != $address2[$field]) {
                    return false;
                }
                if (strtoupper($address1[$field]) != strtoupper($address2[$field])) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Check if in debug mode
     *
     * @return array|mixed
     */
    public function isDebugMode()
    {
        return $this->getConfig('debug');
    }

    /**
     * Return array of enabled domestic APIs
     *
     * @return array
     */
    public function getEnabledDomesticApis()
    {
        return $this->getMultiselectSysConfig('enabled_domestic_apis');
    }

    /**
     * Return array of enabled international APIs
     *
     * @return array
     */
    public function getEnabledInternationalApis()
    {
        return $this->getMultiselectSysConfig('enabled_international_apis');
    }

    /**
     * Get intuitive multiselect value from system config
     *
     * @param $field
     * @param $group
     * @return array
     */
    public function getMultiselectSysConfig($field, $group = false)
    {
        $stringValue = $this->getConfig($field, $group);
        if (empty($stringValue)) {
            return array();
        }
        return explode(',', $stringValue);
    }

    /**
     * Checks if $address has the required tags for the API request
     *
     * @param array $address
     * @return bool
     */
    public function validateAddressFields(array $address)
    {
        $cityAndState = (!empty($address[AddressField::CITY]) && !empty($address[AddressField::REGION_ID]));
        $zip = !empty($address[AddressField::POSTCODE]);
        $street = !empty($address[AddressField::STREET_LINE_1]);

        return ($street
            && ($zip || $cityAndState)
        );
    }
}
