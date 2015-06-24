<?php
/**
 * @package     BlueAcorn\AddressValidation
 * @version     0.1.0
 * @author      Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2015 Blue Acorn, Inc.
 */
class BlueAcorn_AddressValidation_Model_System_Config_Source_Allowance
{
    const REQUIRE_VALIDATE_NONE = 0;
    const REQUIRE_VALIDATE_ONE = 1;
    const REQUIRE_VALIDATE_ALL = 2;

    public function toOptionArray(){
        return array(
            array(
                'label' => Mage::helper('blueacorn_addressvalidation')->__('Validated by none of the enabled APIs'),
                'value' => self::REQUIRE_VALIDATE_NONE
            ),
            array(
                'label' => Mage::helper('blueacorn_addressvalidation')->__('Validated by at least one enabled API'),
                'value' => self::REQUIRE_VALIDATE_ONE
            ),
            array(
                'label' => Mage::helper('blueacorn_addressvalidation')->__('Validated by all of the enabled APIs'),
                'value' => self::REQUIRE_VALIDATE_ALL
            )
        );
    }
}
