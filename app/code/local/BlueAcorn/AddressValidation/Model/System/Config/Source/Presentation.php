<?php
/**
 * @package     BlueAcorn\AddressValidation
 * @version     0.2.0
 * @author      Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2015 Blue Acorn, Inc.
 */

class BlueAcorn_AddressValidation_Model_System_Config_Source_Presentation
{
    const MODAL = 1;
    const ONEPAGE_STEP = 2;

    /**
     * Get sys config options for Presentation select
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array(
                'label' => Mage::helper('blueacorn_addressvalidation')->__('Modal'),
                'value' => self::MODAL
            ),
            array(
                'label' => Mage::helper('blueacorn_addressvalidation')->__('Inline Slide Action'),
                'value' => self::ONEPAGE_STEP
            )
        );
    }
}