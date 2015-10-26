<?php
/**
 * @package     BlueAcorn\AddressValidation
 * @version     0.2.0
 * @author      Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2015 Blue Acorn, Inc.
 */
class BlueAcorn_AddressValidation_Model_System_Config_Source_Api_International
{
    const STRIKEIRON = 'strikeiron';

    /**
     * Get sys config options for Presentation select
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array(
                'label' => Mage::helper('blueacorn_addressvalidation')->__('StrikeIron'),
                'value' => self::STRIKEIRON
            )
        );
    }
}
