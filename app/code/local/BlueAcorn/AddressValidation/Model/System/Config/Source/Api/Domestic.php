<?php
/**
 * @package     BlueAcorn\AddressValidation
 * @version     0.2.0
 * @author      Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2015 Blue Acorn, Inc.
 */
class BlueAcorn_AddressValidation_Model_System_Config_Source_Api_Domestic
    extends BlueAcorn_AddressValidation_Model_System_Config_Source_ApiAbstract
{
    /**
     * System config values should be model shortnames that implement the ApiInterface
     */
    const USPS = 'blueacorn_addressvalidation/validation_api_usps';
    const FEDEX = 'blueacorn_addressvalidation/validation_api_fedex';

    /**
     * Get sys config options for domestic API select
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array_filter(array(
            array(
                'label' => Mage::helper('blueacorn_addressvalidation')->__('Usps'),
                'value' => self::USPS
            ),
            array(
                'label' => Mage::helper('blueacorn_addressvalidation')->__('Fedex'),
                'value' => self::FEDEX
            )
        ), array($this, '_filterImplements'));
    }
}
