<?php
/**
 * @package     BlueAcorn\AddressValidation
 * @version     0.2.0
 * @author      Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2015 Blue Acorn, Inc.
 */
class BlueAcorn_AddressValidation_Model_Zipcode_Api_Usps
    extends BlueAcorn_AddressValidation_Model_ApiAbstract
    implements BlueAcorn_AddressValidation_Model_Zipcode_ApiInterface
{
    use BlueAcorn_AddressValidation_Model_UspsTrait;

    /**
     * Api parameter value; i.e., ?API=CityStateLookup
     *
     * @var string
     */
    protected $_api = 'CityStateLookup';

    /**
     * Lookup zipcode to get city/state from USPS lookup webtool
     * If no exception is thrown, returns associative array with keys
     * 'postcode', 'city', 'state', 'region_id'
     *
     * @param string $zipcode
     * @return array
     */
    public function lookupZipcode($zipcode)
    {
        return $this->_uspsLookup($zipcode);
    }

    /**
     * Makes the API call to look up city/state for $zipcode argument
     *
     * @param $zipcode
     * @return array
     * @throws Mage_Api_Exception
     * @throws Zend_Http_Client_Exception
     */
    protected function _uspsLookup($zipcode)
    {
        $this->_helper->debug('Initial zipcode request: ' . print_r($zipcode, true), null, 'Usps');
        $requestXml = $this->_getRequestXml($zipcode);
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
     * Create USPS CityStateLookup request XML based on $zipcode
     *
     * @param $zipcode
     * @return string
     * @throws Mage_Api_Exception
     */
    protected function _getRequestXml($zipcode)
    {
        // Explicitly cast to string of length 5 to avoid nonsense requests
        $zipcode = (string)$zipcode;
        $zipcode = substr($zipcode, 0, 5);
        if (!is_numeric($zipcode) || strlen($zipcode) < 5) {
            throw new Mage_Api_Exception(self::REQUEST_ERROR,
                'Zipcode request should be a numeric string with at least 5 characters.' . PHP_EOL
                . 'Zipcode requested: ' . $zipcode
            );
        }
        $userId = $this->_getUserId();
        if (!$userId) {
            throw new Mage_Api_Exception(self::REQUEST_ERROR,
                'User ID must be specified in system configuration carriers/usps/userid'
            );
        }

        // Create XML request
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><CityStateLookupRequest/>');
        $xml->addAttribute('USERID', $userId);
        // Add ZipCode and Zip5 node, both required
        $zipcodeNode = $xml->addChild('ZipCode');
        $zipcodeNode->addChild('Zip5', $zipcode);

        // Ensure we are returning a string value, otherwise throw exception for bad request
        $request = $xml->asXML();
        if (!$request) {
            throw new Mage_Api_Exception(self::REQUEST_ERROR,
                'Error generating XML request for USPS city/state lookup.'
            );
        }
        return $request;
    }

    /**
     * Parse XML response into array with keys:
     * 'postcode', 'city', 'state', 'region_id'
     *
     * @param $response
     * @return array
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
                } else if ($xml->getName() == 'CityStateLookupResponse') {
                    if (!empty($xml->ZipCode)) {
                        $zipcode = $xml->ZipCode;
                        if (!empty($zipcode->Error)) {
                            $this->_helper->debug(
                                'Error in city/state lookup response: ' . print_r($zipcode->Error, true),
                                null,
                                'Usps'
                            );
                        }
                        $parsedResponse = array(
                            'postcode' => (string)$zipcode->Zip5,
                            'city' => (string)$zipcode->City,
                            'state' => (string)$zipcode->State,
                            'region_id' => ''
                        );
                        if (!empty($parsedResponse['state'])) {
                            $parsedResponse['region_id'] = Mage::helper('blueacorn_addressvalidation')
                                ->getRegionId($parsedResponse['state']);
                        }
                    }
                    if ($this->_debug) {
                        $info = 'Parsed XML response: ' . PHP_EOL . print_r($parsedResponse, true) . PHP_EOL;
                        $this->_helper->debug($info, null, 'Usps');
                    }

                    return $parsedResponse;
                }
            }
        }
        throw new Mage_Api_Exception(self::RESPONSE_ERROR,
            'XML response object not received. This could be due to an uncaught error in request.'
        );
    }
}