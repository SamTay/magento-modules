<?php
/**
 * @package     BlueAcorn\AddressValidation
 * @version     0.1.0
 * @author      Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2015 Blue Acorn, Inc.
 */ 
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
        $group = $group ? $group : $this->_defaultGroup;
        // If you want a field
        if ($field && array_key_exists($field, $this->_config[$group])) {
            return $this->_config[$group][$field];
        }
        // If you only want group
        if (!$field && $group && array_key_exists($group, $this->_config)) {
            return $this->_config[$group];
        }
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
     * Check if in debug mode
     *
     * @return array|mixed
     */
    public function isDebugMode()
    {
        return $this->getConfig('debug');
    }

    /**
     * Return array of enabled APIs (these are the VALUES, i.e., constants of the source model class), but
     * converted to lower case for convenience.
     */
    public function getEnabledApis()
    {
        return explode(',', strtolower($this->getConfig('enabled_apis')));
    }
}
