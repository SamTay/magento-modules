<?php
/**
 * @package     BlueAcorn\AddressValidation
 * @version     0.1.0
 * @author      Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2015 Blue Acorn, Inc.
 */
class BlueAcorn_AddressValidation_Model_Api_Fedex
    extends BlueAcorn_AddressValidation_Model_ApiAbstract
    implements BlueAcorn_AddressValidation_Model_ApiInterface
{
    const FEDEX_SANDBOX_MODE = 'carriers/fedex/sandbox_mode';
    const FEDEX_SANDBOX_URL = 'https://wsbeta.fedex.com:443/web-services';
    const FEDEX_LIVE_URL = 'https://ws.fedex.com:443/web-services';

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
     * Default cgi gateway URL
     * @var string
     */
    protected $_soapUrl;

    /**
     * Local WSDL for Address Validation
     * FedEx does not provide a web endpoint for this.
     * @var string
     */
    protected $_addressValidationWsdl;

    /**
     * Add helper/debug properties and api settings
     */
    public function __construct()
    {
        parent::__construct();
        $this->_soapUrl = $this->_getSoapUrl();
        $this->_addressValidationWsdl = $this->_getWsdlUrl();
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
                'Missing required XML tags for FedEx Address Validation.'
            );
        }
        $this->setAddress($address);
        $this->_result = $this->_getFedexValidation();
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
    protected function _getFedexValidation()
    {
        if ($this->_debug) {
            $this->_helper->log('Initial address request array:' . PHP_EOL . print_r($this->_address, true), null, 'FedEx');
        }

        try {
            $client = new SoapClient($this->_addressValidationWsdl, array('trace' => 1));
            $client->__setLocation($this->_soapUrl);

            $request = $this->_createValidationRequest($this->_address);

            $response = $client->addressValidation($request);

            if ($this->_debug) {
                $this->_helper->log("Response: \n" . print_r($response, true), null, 'FedEx');
            }

            if ($response->HighestSeverity == 'ERROR' &&
                $response->Notifications->Code == 1000) {
                throw new Mage_Api_Exception(self::REQUEST_ERROR, 'Authentication Failure with FedEx API');
            }

            return $this->_parseSoapResponse($response);

        } catch (Mage_Api_Exception $e) {
            if ($this->_debug) {
                $this->_helper->log($e->getCustomMessage(), null, 'FedEx');
            }
        } catch (Exception $e) {
            switch ($e->getMessage()) {
                // assuming more cases to come later
                case 'Could not connect to host':
                    $errorMsg = 'Error: Could not connect to host for FedEx Web Service API.';
                    break;

                default:
                    $errorMsg = $e->getMessage();
                    break;
            }

            if ($this->_debug) {
                $this->_helper->log($errorMsg, null, 'FedEx');
            }
        }

        return false;
    }

    /**
     * Get SOAP URL endpoint
     *
     * @return string
     */
    protected function _getSoapUrl()
    {
        $isSandboxMode = Mage::getStoreConfigFlag(self::FEDEX_SANDBOX_MODE, Mage::app()->getStore());
        $soapUrl = $isSandboxMode ? self::FEDEX_SANDBOX_URL : self::FEDEX_LIVE_URL;

        if ($this->_debug) {
            $this->_helper->log('SOAP URL is ' . $soapUrl, null, 'FedEx');
        }

        return $soapUrl;
    }

    /**
     * Build WSDL URL from local file
     *
     * @return string
     */
    protected function _getWsdlUrl()
    {
        $wsdlBasePath = Mage::getModuleDir('etc', 'BlueAcorn_AddressValidation')  . DS . 'wsdl' . DS . 'Fedex' . DS;
        $wsdlUrl = $wsdlBasePath . 'AddressValidationService_v3.wsdl';

        if ($this->_debug) {
            $this->_helper->log('WSDL URL is ' . $wsdlUrl, null, 'Fedex');
        }

        return $wsdlUrl;
    }

    /**
     * Creates validation request to send to FedEx API
     *
     * @param array $address
     * @return array
     */
    protected function _createValidationRequest(array $address)
    {
        $fedexCreds = array(
            'Key' => Mage::getStoreConfig('carriers/fedex/key'),
            'Password' => Mage::getStoreConfig('carriers/fedex/password'),
            'AccountNumber' => Mage::getStoreConfig('carriers/fedex/account'),
            'MeterNumber' => Mage::getStoreConfig('carriers/fedex/meter_number'),
        );

        $request = array();

        $street1 = $address['street1'];
        $street2 = $address['street2'];
        $regionId = $address['region_id'];
        $state = $regionId ? $this->_helper->getState($regionId) : null;

        $formattedAddress = array(
            'ClientReferenceId' => 'ClientReferenceId1',
            'Address' =>
                array(
                    'StreetLines' => array($street1, $street2),
                    'PostalCode' => $address['postcode'],
                    'City' => $address['city'],
                    'StateorProvinceCode' => $state,
                    'Company' => '',
                )
        );

        $request['WebAuthenticationDetail'] = array(
            'UserCredential' => array(
                'Key' => $fedexCreds['Key'],
                'Password' => $fedexCreds['Password'],
            )
        );

        $request['ClientDetail'] = array(
            'AccountNumber' => $fedexCreds['AccountNumber'],
            'MeterNumber' => $fedexCreds['MeterNumber'],
        );

        $request['TransactionDetail'] = array('CustomerTransactionId' => ' *** Address Validation Request using PHP ***');

        $request['Version'] = array(
            'ServiceId' => 'aval',
            'Major' => '3',
            'Intermediate' => '0',
            'Minor' => '0'
        );

        $request['InEffectAsOfTimestamp'] = date('c');

        $request['AddressesToValidate'] = array(
            0 => $formattedAddress,
        );

        if ($this->_debug) {
            $this->_helper->log("Request: \n" . print_r($request, true), null, 'FedEx');
        }

        return $request;
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
                $address['region_id'] = $this->_helper->getRegionId($address['state']);
            }
            $result->addAddress($address);
        }
        if (!is_null($returnText)) {
            $result->addMessage($returnText);
        }

        return $result;
    }

    /**
     * Convert SOAP response object into validated address object
     *
     * @param stdClass $response
     * @return BlueAcorn_AddressValidation_Model_Result
     */
    protected function _parseSoapResponse($response)
    {
        $data = $response->AddressResults->EffectiveAddress;
        $validatedAddress = array();

        $validatedAddress['city'] = $data->City;
        $validatedAddress['state'] = $data->StateOrProvinceCode;

        if (is_array($data->StreetLines)) {
            $streetAddress = $data->StreetLines;
            $validatedAddress['street1'] = $streetAddress[0];
            $validatedAddress['street2'] = $streetAddress[1];
        } else {
            $validatedAddress['street1'] = $data->StreetLines;
        }

        if (preg_match('/-/', $data->PostalCode)) {
            $postcode = explode('-', $data->PostalCode);
            $validatedAddress['postcode'] = $postcode[0];
            $validatedAddress['zip4'] = $postcode[1];
        } else {
            $validatedAddress['postcode'] = $data->PostalCode;
        }

        $validatedAddresses[] = $validatedAddress;

        return $this->_convertToResult($validatedAddresses);
    }
}