<?php
/**
 * @package     BlueAcorn\AddressValidation
 * @version     0.1.0
 * @author      Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2015 Blue Acorn, Inc.
 */

/**
 * Interface BlueAcorn_AddressValidation_Model_ApiInterface
 * All address validation APIs are required to implement ApiInterface
 */
interface BlueAcorn_AddressValidation_Model_ApiInterface
{
    const RESPONSE_ERROR = 'RESPONSE_ERROR';
    const REQUEST_ERROR = 'REQUEST_ERROR';

    /**
     * @param array $address
     * @return BlueAcorn_AddressValidation_Model_Result
     */
    public function validateAddress(array $address);
}