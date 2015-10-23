<?php
/**
 * @package     BlueAcorn\AddressValidation
 * @version     0.1.0
 * @author      Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2015 Blue Acorn, Inc.
 */
use BlueAcorn_AddressValidation_Helper_Constants as AddressField;
class BlueAcorn_AddressValidation_Model_Validation_Api_Fedex
    extends BlueAcorn_AddressValidation_Model_ApiAbstract
    implements BlueAcorn_AddressValidation_Model_Validation_ApiInterface
{
    use BlueAcorn_AddressValidation_Model_Validation_ValidationTrait;

    const FEDEX_SANDBOX_MODE = 'carriers/fedex/sandbox_mode';
    const FEDEX_SANDBOX_URL = 'https://wsbeta.fedex.com:443/web-services';
    const FEDEX_LIVE_URL = 'https://ws.fedex.com:443/web-services';

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
     * @return BlueAcorn_AddressValidation_Model_Validation_Result
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
     * Makes the API call to validate $this->_address
     *
     * @throws Mage_Api_Exception
     * @throws Zend_Http_Client_Exception
     */
    protected function _getFedexValidation()
    {
        $this->_helper->debug('Initial address request array:' . PHP_EOL . print_r($this->_address, true), null, 'FedEx');

        try {
            $client = new SoapClient($this->_addressValidationWsdl, array('trace' => 1));
            $client->__setLocation($this->_soapUrl);

            $request = $this->_createValidationRequest($this->_address);

            $response = $client->addressValidation($request);

            $this->_helper->debug("Response: \n" . print_r($response, true), null, 'FedEx');

            if ($response->HighestSeverity == 'ERROR' &&
                $response->Notifications->Code == 1000) {
                throw new Mage_Api_Exception(self::REQUEST_ERROR, 'Authentication Failure with FedEx API');
            }

            return $this->_parseSoapResponse($response);

        } catch (Mage_Api_Exception $e) {
            $this->_helper->debug($e->getCustomMessage(), null, 'FedEx');
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

            $this->_helper->debug($errorMsg, null, 'FedEx');
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

        $this->_helper->debug('SOAP URL is ' . $soapUrl, null, 'FedEx');

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

        $this->_helper->debug('WSDL URL is ' . $wsdlUrl, null, 'Fedex');

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

        $street1 = $address[AddressField::STREET_LINE_1];
        $street2 = $address[AddressField::STREET_LINE_2];
        $regionId = $address[AddressField::REGION_ID];
        $postcode = $address[AddressField::POSTCODE];
        $city = $address[AddressField::CITY];
        $state = $regionId ? $this->_helper->getState($regionId) : null;

        $formattedAddress = array(
            'ClientReferenceId' => 'ClientReferenceId1',
            'Address' =>
                array(
                    'StreetLines' => array($street1, $street2),
                    'PostalCode' => $postcode,
                    'City' => $city,
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

        $this->_helper->debug("Request: \n" . print_r($request, true), null, 'FedEx');

        return $request;
    }

    /**
     * Convert SOAP response object into validated address object
     *
     * @param stdClass $response
     * @return BlueAcorn_AddressValidation_Model_Validation_Result
     */
    protected function _parseSoapResponse($response)
    {
        $data = $response->AddressResults->EffectiveAddress;
        $validatedAddress = array();

        $validatedAddress[AddressField::CITY] = $data->City;
        $validatedAddress[AddressField::STATE] = $data->StateOrProvinceCode;

        if (is_array($data->StreetLines)) {
            $streetAddress = $data->StreetLines;
            $validatedAddress[AddressField::STREET_LINE_1] = $streetAddress[0];
            $validatedAddress[AddressField::STREET_LINE_2] = $streetAddress[1];
        } else {
            $validatedAddress[AddressField::STREET_LINE_1] = $data->StreetLines;
        }

        if (preg_match('/-/', $data->PostalCode)) {
            $postcode = explode('-', $data->PostalCode);
            $validatedAddress[AddressField::POSTCODE] = $postcode[0];
            $validatedAddress[AddressField::ZIP4] = $postcode[1];
        } else {
            $validatedAddress[AddressField::POSTCODE] = $data->PostalCode;
        }

        $validatedAddresses[] = $validatedAddress;

        return $this->_convertArrayToResult($validatedAddresses);
    }
}