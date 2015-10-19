<?php
/**
 * @package     BlueAcorn\AddressValidation
 * @version     0.1.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2015 Blue Acorn, Inc.
 */

/**
 * Class BlueAcorn_AddressValidation_Model_ApiAbstract
 * Optional class for APIs to extend to automatically give helper/debug properties
 */
abstract class BlueAcorn_AddressValidation_Model_ApiAbstract
{
    /**
     * Hold instance of module helper
     * @var BlueAcorn_AddressValidation_Helper_Data
     */
    protected $_helper;

    /**
     * Debug mode on/off
     * @var bool
     */
    protected $_debug = false;

    /**
     * Instantiate this model with helper and debug properties
     */
    public function __construct()
    {
        $this->_helper = Mage::helper('blueacorn_addressvalidation');
        $this->_debug = $this->_helper->isDebugMode();
    }
}
