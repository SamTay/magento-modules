<?php
/**
 * @package     BlueAcorn\AddressValidation
 * @version     0.2.0
 * @author      Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2015 Blue Acorn, Inc.
 */

use BlueAcorn_AddressValidation_Helper_Constants as AddressField;
class BlueAcorn_AddressValidation_Model_Validation_Api_Usps
    extends BlueAcorn_AddressValidation_Model_ApiAbstract
    implements BlueAcorn_AddressValidation_Model_Validation_ApiInterface
{
    use BlueAcorn_AddressValidation_Model_UspsTrait,
        BlueAcorn_AddressValidation_Model_Validation_ValidationTrait;

    /**
     * Api parameter value; i.e., ?API=Verify
     * @var string
     */
    protected $_api = 'Verify';

    /**
     * Address field map BA -> USPS
     * USPS swaps street lines 1 and 2
     * @var array
     */
    protected $_addressFieldMap = array(
        AddressField::STREET_LINE_2 => 'Address1',
        AddressField::STREET_LINE_1 => 'Address2',
        AddressField::CITY => 'City',
        AddressField::STATE => 'State',
        AddressField::POSTCODE => 'Zip5',
        AddressField::ZIP4 => 'Zip4'
    );

    /**
     * Accepts array with address data, sets request in XML format and calls
     * the API to retrieve validation and suggested addresses.
     *
     * @param array $address
     * @return BlueAcorn_AddressValidation_Model_Validation_Result
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
     * Makes the API call to validate $this->_address
     *
     * @throws Mage_Api_Exception
     * @throws Zend_Http_Client_Exception
     */
    protected function _getUspsValidation()
    {
        $requestXml = $this->_parseAddressDataToXml();
        $url = $this->_getGatewayUrl();
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
        $this->_helper->debug('Initial address request array:' . PHP_EOL . print_r($this->_address, true), null, 'Usps');
        $userId = $this->_getUserId();
        if (!$userId) {
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

        // Set state from region ID
        if ($this->_address[AddressField::REGION_ID]) {
            $this->_address[AddressField::STATE] = Mage::helper('blueacorn_addressvalidation')
                ->getState($this->_address[AddressField::REGION_ID]);
        } else {
            $this->_address[AddressField::STATE] = null;
        }
        $this->_address[AddressField::ZIP4] = null;

        // Add all fields in $this->_addressFieldMap
        foreach($this->_addressFieldMap as $baKey => $uspsKey) {
            $addressNode->addChild($uspsKey, $this->_address[$baKey]);
        }

        return $xml->asXML();
    }

    /**
     * @param string $response
     * @return BlueAcorn_AddressValidation_Model_Validation_Result
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
                        foreach($this->_addressFieldMap as $baKey => $uspsKey) {
                            $validatedAddress[$baKey] = (string)$address->$uspsKey;
                        }
                        $validatedAddresses[] = $validatedAddress;
                        if (!empty($address->Error)) {
                            $returnText[] = (string)$address->Error->Description;
                        }
                    }
                    //TODO: Test the return text feature
                    if (!empty($xml->ReturnText)) {
                        $returnText[] = (string)$xml->ReturnText;
                    }

                    if ($this->_debug) {
                        $info = 'Parsed XML response arrays: ' . PHP_EOL
                            . 'Validated addresses: ' . print_r($validatedAddresses, true) . PHP_EOL
                            . 'Return text: ' . print_r($returnText, true);
                        $this->_helper->debug($info, null, 'Usps');
                    }
                    return $this->_convertArrayToResult($validatedAddresses, $returnText);
                }
            }
        }
        throw new Mage_Api_Exception(self::RESPONSE_ERROR,
            'XML response object not received. This could be due to an uncaught error in request.'
        );
    }
}