<?php
/**
 * @package     BlueAcorn\AddressValidation
 * @version     0.2.0
 * @author      Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2015 Blue Acorn, Inc.
 */

/**
 * Interface BlueAcorn_AddressValidation_Model_Validation_ApiInterface
 * All address validation APIs are required to implement this interface
 */
interface BlueAcorn_AddressValidation_Model_Validation_ApiInterface
{
    /**
     * @param array $address
     * @return BlueAcorn_AddressValidation_Model_Validation_Result
     */
    public function validateAddress(array $address);
}