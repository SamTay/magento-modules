<?php
/**
 * @package     BlueAcorn\AddressValidation
 * @version     0.1.0
 * @author      Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2015 Blue Acorn, Inc.
 */

interface BlueAcorn_AddressValidation_Model_ApiInterface
{
    const RESPONSE_ERROR = 'RESPONSE_ERROR';
    const REQUEST_ERROR = 'REQUEST_ERROR';

    public function validateAddress(Varien_Object $address);
}