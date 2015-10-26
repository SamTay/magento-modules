<?php
/**
 * @package     BlueAcorn\AddressValidation
 * @version     0.2.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2015 Blue Acorn, Inc.
 */

/**
 * Interface BlueAcorn_AddressValidation_Model_Zipcode_ApiInterface
 * All zipcode lookup tools are required to implement this interface
 */
interface BlueAcorn_AddressValidation_Model_Zipcode_ApiInterface
{
    /**
     * @param string $zipcode
     * @return array
     */
    public function lookupZipcode($zipcode);
}