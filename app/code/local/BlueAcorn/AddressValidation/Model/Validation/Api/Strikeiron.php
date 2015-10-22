<?php
/**
 * @package     BlueAcorn\AddressValidation
 * @version     0.1.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2015 Blue Acorn, Inc.
 */
use BlueAcorn_AddressValidation_Helper_Constants as AddressField;
class BlueAcorn_AddressValidation_Model_Validation_Api_Strikeiron
    extends BlueAcorn_AddressValidation_Model_ApiAbstract
    implements BlueAcorn_AddressValidation_Model_Validation_ApiInterface
{
    use BlueAcorn_AddressValidation_Model_Validation_ValidationTrait;

    const SYS_CONFIG_PATH_USERID = 'blueacorn_addressvalidation/strikeiron/user_id';
    const SYS_CONFIG_PATH_PASSWORD = 'blueacorn_addressvalidation/strikeiron/password';
    const SYS_CONFIG_PATH_BASEURL = 'blueacorn_addressvalidation/strikeiron/base_url';

    /**
     * Default base URL (system config overrides this)
     * @var string
     */
    protected $_baseUrl = 'http://ws.strikeiron.com/StrikeIron/GlobalAddressVerification5/GlobalAddressVerification/';

    /**
     * Api method
     * @var string
     */
    protected $_api = 'BasicVerify';

    /**
     * Prefix for all address param keys
     * @var string
     */
    protected $_addressParamPrefix = 'BasicVerify.';

    /**
     * Prefix for all login param keys
     * @var string
     */
    protected $_loginParamPrefix = 'LicenseInfo.RegisteredUser.';

    /**
     * Address field map for StrikeIron specific params
     * @var array
     */
    protected $_addressFieldMap = array(
        AddressField::STREET_LINE_1 => 'StreetAddressLines',
        AddressField::STREET_LINE_2 => 'StreetAddressLines',
        AddressField::CITY => 'CountrySpecificLocalityLine',
        AddressField::POSTCODE => 'CountrySpecificLocalityLine',
        AddressField::STATE => 'CountrySpecificLocalityLine',
        AddressField::COUNTRY => 'Country'
    );

    /**
     * Accepts array with address data, converts to REST request and hits
     * the API to retrieve validation and suggested addresses.
     *
     * @param array $address
     * @return BlueAcorn_AddressValidation_Model_Validation_Result
     */
    public function validateAddress(array $address)
    {
        // TODO: Check if strikeiron has REQUIRED fields and validate here
        $this->setAddress($address);
        $this->_result = $this->_getStrikeironValidation();
        return $this->getResult();
    }

    /**
     * Makes the API call to validate $this->_address
     *
     * @return mixed
     * @throws Mage_Api_Exception
     * @throws Zend_Http_Client_Exception
     */
    protected function _getStrikeironValidation()
    {
        $addressParams = $this->_parseAddressToParams();
        $loginParams = $this->_getLoginParams();
        $url = $this->_getBaseUrl() . $this->_api;
        $client = new Zend_Http_Client();
        $client->setUri($url)
            ->setConfig(array('maxredirects' => 0, 'timeout' => 30));
        foreach ($loginParams as $key => $loginParam) {
            $client->setParameterGet($this->_loginParamPrefix . $key, $loginParam);
        }
        foreach($addressParams as $key => $addressParam) {
            $client->setParameterGet($this->_addressParamPrefix . $key, $addressParam);
        }
        $client->setParameterGet('format', 'JSON');

        $response = $client->request();
        $responseBody = $response->getBody();
        return $this->_parseJsonResponse($responseBody);
    }

    /**
     * Parse $this->_address array into StrikeIron param => value pairs
     *
     * @return array
     */
    protected function _parseAddressToParams()
    {
        if ($this->_debug) {
            $this->_helper->log('Initial address request array:' . PHP_EOL . print_r($this->_address, true), null, 'Strikeiron');
        }

        $params = array();
        foreach($this->_addressFieldMap as $baKey => $siKey) {
            if (empty($this->_address[$baKey])) {
                continue;
            }
            $params[$siKey] = empty($params[$siKey])
                ? $this->_address[$baKey]
                : $params[$siKey] . ' ' . $this->_address[$baKey];
        }

        if ($this->_debug) {
            $this->_helper->log('Converted StrikeIron address params:' . PHP_EOL . print_r($params, true), null, 'Strikeiron');
        }

        return $params;
    }

    /**
     * Parse response json and return result
     * @param $response
     * @throws Zend_Json_Exception
     * @return BlueAcorn_AddressValidation_Model_Validation_Result
     */
    protected function _parseJsonResponse($response)
    {
        //TODO: Finish writing after seeing response - need active credentials
        $response = Zend_Json::decode($response);
        $this->_helper->log('TEST RESPONSE:' . PHP_EOL . print_r($response, true), null, 'Strikeiron');
        $validatedAddresses = array();
        return $this->_convertArrayToResult($validatedAddresses);
    }

    /**
     * Get base url from sys config, default to hardcoded property above
     *
     * @return string
     */
    protected function _getBaseUrl()
    {
        return Mage::getStoreConfig(self::SYS_CONFIG_PATH_BASEURL) ?: $this->_baseUrl;
    }

    /**
     * Get StrikeIron credentials as array of "url_param_key"=>"value" pairs
     *
     * @return array
     * @throws Mage_Api_Exception
     */
    protected function _getLoginParams()
    {
        $userId = Mage::getStoreConfig(self::SYS_CONFIG_PATH_USERID);
        $password = Mage::getStoreConfig(self::SYS_CONFIG_PATH_PASSWORD);

        if (!$userId || !$password) {
            throw new Mage_Api_Exception(
                self::REQUEST_ERROR,
                'StrikeIron requires user id and password, which must be set in system configuration.'
            );
        }

        return array('UserID' => $userId, 'Password' => $password);
    }
}
