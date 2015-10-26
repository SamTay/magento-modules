<?php
/**
 * @package     BlueAcorn\AddressValidation
 * @version     0.2.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2015 Blue Acorn, Inc.
 */
class BlueAcorn_AddressValidation_Model_System_Config_Source_ApiAbstract
{
    /**
     * Array filtering method to ensure that api source models implement ApiInterface
     *
     * @param $option
     * @return string
     */
    protected function _filterImplements($option)
    {
        if (is_array($option) && array_key_exists('value', $option)) {
            $apiInterface = 'BlueAcorn_AddressValidation_Model_Validation_ApiInterface';
            $optionClassName = Mage::app()->getConfig()->getModelClassName($option['value']);
            $optionReflection = new ReflectionClass($optionClassName);
            return $optionReflection->implementsInterface($apiInterface);
        }
        return false;
    }
}