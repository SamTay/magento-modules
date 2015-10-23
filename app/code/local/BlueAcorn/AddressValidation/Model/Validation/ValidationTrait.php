<?php
/**
 * @package     BlueAcorn\AddressValidation
 * @version     0.1.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2015 Blue Acorn, Inc.
 */
use BlueAcorn_AddressValidation_Helper_Constants as AddressField;
trait BlueAcorn_AddressValidation_Model_Validation_ValidationTrait
{
    /**
     * Address data from form submission
     * @var array
     */
    protected $_address;

    /**
     * @var BlueAcorn_AddressValidation_Model_Validation_Result
     */
    protected $_result;

    /**
     * Set address to validate
     *
     * @param array $address
     */
    public function setAddress(array $address)
    {
        $this->_address = $address;
    }

    /**
     * Get result of API call
     *
     * @return BlueAcorn_AddressValidation_Model_Validation_Result
     */
    public function getResult()
    {
        return $this->_result;
    }

    /**
     * Converts address arrays and return text to the proper Result object
     *
     * @param array $validatedAddresses
     * @param null $returnText
     * @return BlueAcorn_AddressValidation_Model_Validation_Result
     */
    protected function _convertArrayToResult(array $validatedAddresses = array(), $returnText = null)
    {
        $result = Mage::getModel('blueacorn_addressvalidation/validation_result');
        foreach($validatedAddresses as $address) {
            if (isset($address[AddressField::STATE])) {
                $state = $address[AddressField::STATE];
                $address[AddressField::REGION_ID] = Mage::helper('blueacorn_addressvalidation')->getRegionId($state);
            }
            $result->addAddress($address);
        }
        if (!empty($returnText)) {
            $result->addMessage($returnText);
        }

        return $result;
    }
}