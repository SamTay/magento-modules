<?php
/**
 * @package     BlueAcorn\AddressValidation
 * @version     0.2.0
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
    protected $_requestFieldMap = array(
        AddressField::STREET_LINE_1 => 'StreetAddressLines',
        AddressField::STREET_LINE_2 => 'StreetAddressLines',
        AddressField::CITY => 'CountrySpecificLocalityLine',
        AddressField::POSTCODE => 'CountrySpecificLocalityLine',
        AddressField::STATE => 'CountrySpecificLocalityLine',
        AddressField::COUNTRY => 'Country'
    );

    /**
     * Address field map for StrikeIron specific response
     * Not sure what is relevant as "Street Line 2", so passing it the "Residue" for now
     * @var array
     */
    protected $_responseFieldMap = array(
        'DeliveryAddressLine' => AddressField::STREET_LINE_1,
        'PostalCode' => AddressField::POSTCODE,
        'Locality' => AddressField::CITY,
        'Province' => AddressField::STATE,
        'Country' => AddressField::COUNTRY,
        'Residue' => AddressField::STREET_LINE_2
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
        $this->_helper->debug('Initial address request array:' . PHP_EOL . print_r($this->_address, true), null, 'Strikeiron');

        // Attach region name to request address
        if (!empty($this->_address[AddressField::REGION_ID])) {
            // "State" is really the "Region Name" in this case, but trying to have consistent naming across APIs
            $this->_address[AddressField::STATE] = $this->_helper
                ->getRegionName($this->_address[AddressField::REGION_ID]);
        }

        // Use full english country name instead of country ID
        if (!empty($this->_address[AddressField::COUNTRY])) {
            $this->_address[AddressField::COUNTRY] = $this->_helper
                ->getCountryName($this->_address[AddressField::COUNTRY]);
        }

        $params = array();
        foreach($this->_requestFieldMap as $baKey => $siKey) {
            if (empty($this->_address[$baKey])) {
                continue;
            }
            $params[$siKey] = empty($params[$siKey])
                ? $this->_address[$baKey]
                : $params[$siKey] . ' ' . $this->_address[$baKey];
        }

        $this->_helper->debug('Converted StrikeIron address params:' . PHP_EOL . print_r($params, true), null, 'Strikeiron');

        return $params;
    }

    /**
     * Parse response json and return result
     *
     * @param $response
     * @return BlueAcorn_AddressValidation_Model_Validation_Result
     * @throws Mage_Api_Exception
     * @throws Zend_Json_Exception
     */
    protected function _parseJsonResponse($response)
    {
        $response = Zend_Json::decode($response);

        // Traverse down response a few levels
        foreach(array('WebServiceResponse', 'BasicVerifyResponse', 'BasicVerifyResult') as $arrayKey) {
            if (!isset($response[$arrayKey])) {
                throw new Mage_Api_Exception(
                    self::RESPONSE_ERROR,
                    'StrikeIron has no response information, this is likely a missed error in request.'
                );
            }
            $response = $response[$arrayKey];
        }
        // Check response status
        if (isset($response['ServiceStatus'])) {
            $this->_verifyServiceStatus($response['ServiceStatus']);
        }
        // Parse response address
        if (isset($response['ServiceResult'])) {
            return $this->_parseServiceResult($response['ServiceResult']);
        }

        throw new Mage_Api_Exception(self::RESPONSE_ERROR, 'StrikeIron did not supply a ServiceResult.');
    }

    /**
     * Parse response service result into address array, then return converted result object
     *
     * @param $serviceResult
     * @return BlueAcorn_AddressValidation_Model_Validation_Result
     */
    protected function _parseServiceResult($serviceResult)
    {
        /**
         * StrikeIron seems to only give one suggestion, not multiple.
         */
        $validatedAddress = array();
        foreach($this->_responseFieldMap as $siKey => $baKey) {
            $validatedAddress[$baKey] = isset($serviceResult[$siKey]) ? $serviceResult[$siKey] : null;
        }
        // Convert country name into country ID
        if (isset($validatedAddress[AddressField::COUNTRY])) {
            $validatedAddress[AddressField::COUNTRY] = $this->_helper->getCountryId($validatedAddress[AddressField::COUNTRY]);
            // Set region_id on address
            // Here "STATE" is actually the "Region Name" in Magento
            if (isset($validatedAddress[AddressField::STATE])) {
                $validatedAddress[AddressField::REGION_ID] = Mage::helper('blueacorn_addressvalidation')
                    ->getRegionId($validatedAddress[AddressField::STATE], true, $validatedAddress[AddressField::COUNTRY]);
            }
        }
        $this->_helper->debug('Parsed address response: ' . PHP_EOL . print_r($validatedAddress, true), null, 'Strikeiron');

        return $this->_convertArrayToResult(array($validatedAddress));
    }

    /**
     * Checks service status on response and throws exceptions if necessary
     *
     * @param $status
     * @throws Mage_Api_Exception
     */
    protected function _verifyServiceStatus($status)
    {
        $statusNbr = isset($status['StatusNbr']) ? $status['StatusNbr'] : 200; // Assume good response by default
        if (300 < $statusNbr && $statusNbr < 325) {
            // Address can't be corrected, but could be deliverable
            // Possibly create system configuration for how strict this should be
            return;
        }
        if ($statusNbr == 325) {
            // Address can't be corrected and is unlikely to be delivered
            return;
        }
        if (400 < $statusNbr && $statusNbr < 500) {
            // Request issues
            throw new Mage_Api_Exception(self::RESPONSE_ERROR, print_r($status, true));
        }
        if (500 <= $statusNbr) {
            // Server response issues
            throw new Mage_Api_Exception(self::RESPONSE_ERROR, print_r($status, true));
        }
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

    /**
     * Override from trait because of international differences in region code/name in response
     * Converts address arrays and return text to the proper Result object
     *
     * @param array $validatedAddresses
     * @param null $returnText
     * @return BlueAcorn_AddressValidation_Model_Validation_Result
     */
    protected function _convertArrayToResult(array $validatedAddresses = array(), $returnText = null)
    {
        $result = Mage::getModel('blueacorn_addressvalidation/validation_result');
        foreach($validatedAddresses as $address) {
            $result->addAddress($address);
        }
        if (!empty($returnText)) {
            $result->addMessage($returnText);
        }

        return $result;
    }
}
