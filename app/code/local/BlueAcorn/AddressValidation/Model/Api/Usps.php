<?php
/**
 * @package     BlueAcorn\AddressValidation
 * @version     0.1.0
 * @author      Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2015 Blue Acorn, Inc.
 */

class BlueAcorn_AddressValidation_Model_Api_Usps implements BlueAcorn_AddressValidation_Model_ApiInterface
{

    /**
     * Address data from form submission
     * @var Varien_Object
     */
    protected $_address;

    /**
     * @var BlueAcorn_AddressValidation_Model_Result
     */
    protected $_result;

    /**
     * Default cgi gateway URL
     * @var string
     */
    protected $_defaultGatewayUrl = 'http://production.shippingapis.com/ShippingAPI.dll';

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
     * @return BlueAcorn_AddressValidation_Model_Result
     * @throws Mage_Api_Exception
     */
    public function validateAddress(Varien_Object $address)
    {
        if (!$this->_validateAddressFields($address)) {
            throw new Mage_Api_Exception(self::REQUEST_ERROR,
                'Missing required XML tags for USPS Address Standardization.'
            );
        }
        $this->setAddress($address);
        $this->_result = $this->_getUspsValidation();
        return $this->getResult();
    }

    /**
     * Set address to validate
     *
     * @param Varien_Object $address
     */
    public function setAddress(Varien_Object $address)
    {
        $this->_address = $address;
    }

    /**
     * Get result of API call
     *
     * @return BlueAcorn_AddressValidation_Model_Result
     */
    public function getResult()
    {
        return $this->_result;
    }

    /**
     * Makes the API call to validate $this->_address
     *
     * @throws Mage_Api_Exception
     * @throws Zend_Http_Client_Exception
     */
    protected function _getUspsValidation()
    {
        $requestXml = $this->_parseAddressDataToXml();
        $url = Mage::getStoreConfig('carriers/usps/gateway_url') ?: $this->_defaultGatewayUrl;
        $client = new Zend_Http_Client();
        $client->setUri($url)
            ->setConfig(array('maxredirects' => 0, 'timeout' => 30))
            ->setParameterGet('API', $this->_api)
            ->setParameterGet('XML', $requestXml);
        $response = $client->request();
        $responseBody = $response->getBody();

        return $this->_parseXmlResponse($responseBody);
    }

    /**
     * Takes Varien_Object data and forms XML for api request
     *
     * @return SimpleXMLElement
     * @throws Mage_Api_Exception
     */
    protected function _parseAddressDataToXml()
    {
        $userId = Mage::getStoreConfig('carriers/usps/userid');
        if (!$userId) {
            //TODO: Catch exception in controller
            // Exceptions should be logged and controller should return validated (or let result
            // be determined by sys config)
            throw new Mage_Api_Exception(self::REQUEST_ERROR,
                'User ID must be specified in system configuration carriers/usps/userid'
            );
        }
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><AddressValidationRequest/>');
        $xml->addAttribute('USERID', $userId);

        // Optional nodes
        $xml->addChild('IncludeOptionalElements', 'false');
        $xml->addChild('ReturnCarrierRoute', 'false');

        // Address nodes (all required nodes, some optional values)
        $addressNode = $xml->addChild('Address');
        $addressNode->addChild('Address1', $this->_address['street'][1]);
        $addressNode->addChild('Address2', $this->_address['street'][0]);
        $addressNode->addChild('City', $this->_address['city']);
        // Get state from region ID (possibly removed from shipping address)
        if ($this->_address['region_id']) {
            $state = Mage::getModel('directory/region')->getCollection()
                ->addFieldToSelect('code')
                ->addFieldToFilter('main_table.region_id', $this->_address['region_id'])
                ->getFirstItem()
                ->getCode();
        } else {
            $state = null;
        }
        $addressNode->addChild('State', $state);
        $addressNode->addChild('Zip5', $this->_address['postcode']);

        return $xml->asXML();
    }

    /**
     * @param string $response
     * @throws Mage_Api_Exception
     */
    protected function _parseXmlResponse($response)
    {
        if (is_string($response) && strpos(ltrim($response), '<?xml') === 0) {
            $xml = simplexml_load_string($response);
            if (is_object($xml)) {
                if (is_object($xml->Error)) {
                    throw new Mage_Api_Exception(self::RESPONSE_ERROR,
                        'Number: ' . (string)$xml->Error->Number . PHP_EOL
                        . 'Description: ' . (string)$xml->Description
                    );
                }

                //TODO: Parse the expected XML and return valid result object
            }
        }
        //TODO: Throw exception that response was not generated
    }

    /**
     * Checks if $address has the required tags for the API request
     *
     * @param Varien_Object $address
     * @return bool
     */
    protected function _validateAddressFields(Varien_Object $address)
    {
        $cityAndState = (!empty($address['city']) && !empty($address['region_id']));
        $zip = !empty($address['postcode']);
        $street = (!empty($address['street']) && !empty($address['street'][0]));

        return ($street
            && ($zip || $cityAndState)
        );
    }
}