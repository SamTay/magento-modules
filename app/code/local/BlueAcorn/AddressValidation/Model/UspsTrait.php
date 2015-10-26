<?php
/**
 * @package     BlueAcorn\AddressValidation
 * @version     0.2.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2015 Blue Acorn, Inc.
 */
trait BlueAcorn_AddressValidation_Model_UspsTrait
{
    /**
     * Default cgi gateway URL
     * @var string
     */
    protected $_defaultGatewayUrl = 'http://production.shippingapis.com/ShippingAPI.dll';

    /**
     * Get USPS gateway url from system config, or default to trait property
     *
     * @return string
     */
    protected function _getGatewayUrl()
    {
        return Mage::getStoreConfig('carriers/usps/gateway_url') ?: $this->_defaultGatewayUrl;
    }

    /**
     * Get USPS user id from system configuration
     *
     * @return mixed
     */
    protected function _getUserId()
    {
        return Mage::getStoreConfig('carriers/usps/userid');
    }
}