<?php
/**
 * @package     BlueAcorn\AddressValidation
 * @version     0.1.0
 * @author      Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2015 Blue Acorn, Inc.
 */

class BlueAcorn_AddressValidation_Model_Usps implements BlueAcorn_AddressValidation_Model_ValidationInterface
{


    /**
     * Request includes API URL with appended path and XML Request string
     * @var string
     */
    protected $_request;

    /**
     * SimpleXMLElement created from Varien Object address data, holds XML Request as object
     * @var SimpleXMLElement
     */
    protected $_requestXML;

    /**
     * Api parameter value; i.e., ?API=Verify
     * @var string
     */
    protected $_api = 'Verify';


    public function __construct()
    {
    }

    /**
     * Accepts object with address data, sets request in XML format and calls
     * the API to retrieve validation and suggested addresses.
     *
     * TODO: See if it makes sense for multishipping to allow multiple address validations at once,
     * because this is possible with the API and obviously more efficient.
     *
     * @param Varien_Object $address
     */
    public function validateAddress(Varien_Object $address)
    {
        $this->_generateRequest($address);
        $this->_requestXML = $this->_parseAddressData($address);
    }

    public function generateRequest(Varien_Object $address)
    {
        $this->_requestXML = $this->_parseAddressData($address);
        //TODO Form URL using production url (get from sys config), $_api, and $_requestXML->asXml()
    }

    /**
     * Takes Varien_Object data and forms XML for api request
     *
     * @param Varien_Object $address
     * @return SimpleXMLElement
     * @throws Exception
     */
    protected function _parseAddressData(Varien_Object $address)
    {
        $userId = Mage::getStoreConfig('carriers/usps/userid');
        if (!$userId) {
            //TODO: Catch exception in controller
            // Exceptions should be logged and controller should return validated (or let result
            // be determined by sys config)
            throw new Exception('User ID must be specified in system configuration carriers/usps/userid');
        }
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><AddressValidationRequest/>');
        $xml->addAttribute('USERID', $userId);

        $xml->addChild('IncludeOptionalElements', 'false');
        $xml->addChild('ReturnCarrierRoute', 'false');

        $addressNode = $xml->addChild('Address');
        $addressNode->addChild('Address1', $address['street'][1]);
        $addressNode->addChild('Address2', $address['street'][0]);
        $addressNode->addChild('City', $address['city']);
        //TODO: Get state from region_id
        $addressNode->addChild('State');
        $addressNode->addChild('Zip5', $address['postcode']);

        return $xml;
    }
}