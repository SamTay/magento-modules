<?php
/**
 * @package     BlueAcorn\AddressValidation
 * @version     0.2.0
 * @author      Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2015 Blue Acorn, Inc.
 */
class BlueAcorn_AddressValidation_Model_System_Config_Source_Api_International
    extends BlueAcorn_AddressValidation_Model_System_Config_Source_ApiAbstract
{
    /**
     * System config values should be model shortnames that implement the ApiInterface
     */
    const STRIKEIRON = 'blueacorn_addressvalidation/validation_api_strikeiron';

    /**
     * Get sys config options for international API select
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array_filter(array(
            array(
                'label' => Mage::helper('blueacorn_addressvalidation')->__('StrikeIron'),
                'value' => self::STRIKEIRON
            )
        ), array($this, '_filterImplements'));
    }
}
