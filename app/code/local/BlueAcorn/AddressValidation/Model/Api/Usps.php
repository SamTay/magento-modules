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
     * @var array
     */
    protected $_address;

    /**
     * @var BlueAcorn_AddressValidation_Model_Result
     */
    protected $_result;

    /**
     * Hold instance of module helper
     * @var
     */
    protected $_helper;

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

    /**
     * Debug mode on/off
     * @var bool
     */
    protected $_debug = false;

    public function __construct()
    {
        $this->_helper = Mage::helper('blueacorn_addressvalidation');
        $this->_debug = $this->_helper->isDebugMode();
    }

    /**
     * Accepts array with address data, sets request in XML format and calls
     * the API to retrieve validation and suggested addresses.
     *
     * @param array $address
     * @return BlueAcorn_AddressValidation_Model_Result
     * @throws Mage_Api_Exception
     */
    public function validateAddress(array $address)
    {
        if (!$this->_helper->validateAddressFields($address)) {
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
     * @param array $address
     */
    public function setAddress(array $address)
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
        if ($this->_debug) {
            $this->_helper->log('Initial address request array:' . PHP_EOL . print_r($this->_address, true), null, 'Usps');
        }
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
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><AddressValidateRequest/>');
        $xml->addAttribute('USERID', $userId);

        // Optional nodes
        $xml->addChild('IncludeOptionalElements', 'false');
        $xml->addChild('ReturnCarrierRoute', 'false');

        // Address nodes (all required nodes, some optional values)
        $addressNode = $xml->addChild('Address');
        $addressNode->addChild('FirmName');
        $addressNode->addChild('Address1', $this->_address['street2']);
        $addressNode->addChild('Address2', $this->_address['street1']);
        $addressNode->addChild('City', $this->_address['city']);
        // Get state from region ID (possibly removed from shipping address)
        if ($this->_address['region_id']) {
            $state = Mage::helper('blueacorn_addressvalidation')->getState($this->_address['region_id']);
        } else {
            $state = null;
        }
        $addressNode->addChild('State', $state);
        $addressNode->addChild('Zip5', $this->_address['postcode']);
        $addressNode->addChild('Zip4');

        return $xml->asXML();
    }

    /**
     * @param string $response
     * @return BlueAcorn_AddressValidation_Model_Result
     * @throws Mage_Api_Exception
     */
    protected function _parseXmlResponse($response)
    {
        if (is_string($response) && strpos(ltrim($response), '<?xml') === 0) {
            $xml = simplexml_load_string($response);
            if (is_object($xml)) {
                if ($xml->getName() == 'Error') {
                    throw new Mage_Api_Exception(self::RESPONSE_ERROR,
                        'Number: ' . (string)$xml->Number . PHP_EOL
                        . 'Source: ' . (string)$xml->Source . PHP_EOL
                        . 'Description: ' . (string)$xml->Description
                    );
                } else if ($xml->getName() == 'AddressValidateResponse') {
                    $validatedAddresses = array();
                    $returnText = array();
                    foreach ($xml->Address as $address) {
                        $validatedAddress = array();
                        $validatedAddress['city'] = (string)$address->City;
                        $validatedAddress['state'] = (string)$address->State;
                        $validatedAddress['postcode'] = (string)$address->Zip5;
                        $validatedAddress['zip4'] = (string)$address->Zip4;
                        $validatedAddress['street1'] = (string)$address->Address2;
                        $validatedAddress['street2'] = (string)$address->Address1;
                        $validatedAddresses[] = $validatedAddress;
                    }
                    //TODO: Test the return text feature
                    if (!empty($xml->ReturnText)) {
                        $returnText[] = (string)$xml->ReturnText;
                    }
                    if (!empty($address->Error)) {
                        $returnText[] = (string)$address->Error->Description;
                    }

                    if ($this->_debug) {
                        $info = 'Parsed XML response arrays: ' . PHP_EOL
                            . 'Validated addresses: ' . print_r($validatedAddresses, true) . PHP_EOL
                            . 'Return text: ' . print_r($returnText, true);
                        $this->_helper->log($info, null, 'Usps');
                    }
                    return $this->_convertToResult($validatedAddresses, $returnText);
                }
            }
        }
        throw new Mage_Api_Exception(self::RESPONSE_ERROR,
            'XML response object not received. This could be due to an uncaught error in request.'
        );
    }

    /**
     * Converts address arrays and return text to the proper Result object
     *
     * @param array $validatedAddresses
     * @param null $returnText
     * @return BlueAcorn_AddressValidation_Model_Result
     */
    protected function _convertToResult(array $validatedAddresses = array(), $returnText = null)
    {
        $result = Mage::getModel('blueacorn_addressvalidation/result');
        foreach($validatedAddresses as $address) {
            if (isset($address['state'])) {
                $address['region_id'] = Mage::helper('blueacorn_addressvalidation')->getRegionId($address['state']);
            }
            $result->addAddress($address);
        }
        if (!is_null($returnText)) {
            $result->addMessage($returnText);
        }

        return $result;
    }

}