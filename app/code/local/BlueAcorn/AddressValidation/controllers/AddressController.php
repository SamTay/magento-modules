<?php
/**
 * @package     BlueAcorn\AddressValidation
 * @version     0.1.0
 * @author      Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2015 Blue Acorn, Inc.
 */
use BlueAcorn_AddressValidation_Helper_Constants as AddressField;
class BlueAcorn_AddressValidation_AddressController extends Mage_Core_Controller_Front_Action
{

    /**
     * Holds whether or not to abort this validation request (response with http code 500)
     * @var bool
     */
    protected $_abort = false;

    /**
     * Holds whether or not to abort validation request and skip on frontend (response with http code 200)
     * @var bool
     */
    protected $_skipValidation = false;

    /**
     * Holds original address request (with street1 and street2 keys converted)
     * @var array
     */
    protected $_requestAddress = array();

    /**
     * Holds result of validation
     * @var null|BlueAcorn_AddressValidation_Model_Validation_Result
     */
    protected $_result = null;

    /**
     * The fields that make up the request address
     * 'street' is converted into the proper 'sreet1' and 'street2' in $this->_initAddress()
     * @var array
     */
    protected $_addressFields = array(
        'street',
        AddressField::CITY,
        AddressField::REGION_ID,
        AddressField::POSTCODE,
        AddressField::COUNTRY,
    );

    /**
     * Index action (method must exist for router)
     */
    public function indexAction()
    {
        $this->_abstractAction();
    }

    /**
     * Checkout action (method must exist for router)
     */
    public function checkoutAction()
    {
        $this->_abstractAction();
    }

    /**
     * Account action (method must exist for router)
     */
    public function accountAction()
    {
        $this->_abstractAction();
    }

    /**
     * Admin action (method must exist for router)
     */
    public function adminAction()
    {
        $this->_abstractAction();
    }

    /**
     * This is the default action for requests
     * Varies slightly according to $this->getRequest()->getActionName()
     */
    protected function _abstractAction()
    {
        $this->_validate();
        $this->_markPossibleSkips();
        $this->_sendResponse();
    }

    /**
     * Validates against enabled APIs and returns validated address and/or message
     */
    protected function _validate()
    {
        $address = $this->getRequestAddress();

        $apiGetter = 'getEnabled'
            . ($this->isInternational() ? 'International' : 'Domestic')
            . 'Apis';
        $result = Mage::getModel('blueacorn_addressvalidation/validation_result');
        foreach($this->helper()->$apiGetter() as $api) {
            $apiResult = null;
            $shortname = 'blueacorn_addressvalidation/validation_api_' . $api;
            try {
                $apiResult = Mage::getModel($shortname)->validateAddress($address);
            } catch (Mage_Api_Exception $e) {
                $this->helper()->log(
                    $e->getCustomMessage(),
                    $e->getCode(),
                    $api
                );
                /**
                 * If this is a request error, it is likely our fault for attempting to verify an
                 * address with insufficient fields or lack of system configuration for APIs.
                 */
                if ($e->getCode() == BlueAcorn_AddressValidation_Model_ApiAbstract::REQUEST_ERROR) {
                    $this->_abort = true;
                }
            } catch (Exception $e) {
                $this->helper()->log(
                    $e->getMessage(),
                    null,
                    $api
                );
                /**
                 * If this is not an API exception, this is not simply an issue with verifying an address,
                 * but rather an internal error/bug, hence the error will not help the customer.
                 */
                $this->_abort = true;
            }

            if ($apiResult) {
                $result = $result->merge($apiResult);
            }
        }

        $this->_result = $result;
        return $this->_result;
    }

    /**
     * Mark possible skips for validation step, such as equivalence in validated
     * address and request address
     */
    protected function _markPossibleSkips()
    {
        if ($this->_result->getAddressCount() != 1) {
            return;
        }
        $validatedAddress = $this->_result->getFirstAddress();
        if ($this->getConfigPerAction('skip_on_equivalent')) {
            if ($this->helper()->compareAddresses($this->_requestAddress, $validatedAddress)) {
                $this->_skipValidation = true;
                return;
            }
        }
        if ($this->helper()->compareAddresses($this->_requestAddress, $validatedAddress, array(), true)) {
            $this->_skipValidation = true;
        }
    }

    /**
     * Send response to browser, but dispatch event first for further customization of response
     *
     * @throws Zend_Controller_Response_Exception
     */
    protected function _sendResponse()
    {
        $response = new Varien_Object();

        if ($this->getSkipValidation()) {
            $this->getResponse()->setHttpResponseCode(200)
                ->setHeader('Content-Type', 'application/json')
                ->setBody(Mage::helper('core')->jsonEncode(array('skip_validation' => true)));
            return;
        }
        if ($this->_result->hasAddress()) {
            $response['addresses'] = $this->_result->getAddresses();
        } elseif ($this->getAbort()) {
            $this->getResponse()->setHttpResponseCode(500);
            return;
        }

        // Add errors or not
        $response->setDisplayErrors($this->getConfigPerAction('display_errors'));

        // Dispatch event for further customization of response
        Mage::dispatchEvent('ba_addressvalidation_send_response_before', array(
            'controller' => $this,
            'response' => $response
        ));

        // Determine whether this is a modal or checkout step
        if ($response->getForm() || $response->getError()) {
            $presentation = $this->getConfigPerAction('presentation');
            $isModal = (is_null($presentation)
                || $presentation == BlueAcorn_AddressValidation_Model_System_Config_Source_Presentation::MODAL);
            $response->setIsModal($isModal);
        }

        $this->getResponse()->setHttpResponseCode(200)
            ->setHeader('Content-Type', 'application/json')
            ->setBody(Mage::helper('core')->jsonEncode($response));
    }

    /**
     * Return a well formulated address array from the request
     *
     * @return array
     */
    protected function _initAddress()
    {
        $address = array();
        // Intentional assignment - check for user selecting saved shipping address
        if ($id = $this->getRequest()->getParam('shipping_address_id')) {
            $request = Mage::getModel('customer/address')->load($id)->getData();
            $address['entity_id'] = $id;
        // Otherwise, they must have filled out the full shipping form
        } elseif ($this->getRequest()->getParam('shipping')) {
            // In checkout, these fields are under shipping param
            $request = $this->getRequest()->getParam('shipping');
        } else {
            // By default, assume fields are just in request params singularly
            $request = $this->getRequest()->getParams();
        }

        // Sanitize request
        foreach($this->_addressFields as $field) {
            $address[$field] = isset($request[$field]) ? $request[$field] : null;
        }
        // Get country
        if (is_null($address[AddressField::COUNTRY]) && isset($request['country_id'])) {
            $address[AddressField::COUNTRY] = $request['country_id'];
        }
        // Get state code from region ID
        if (!is_null($address[AddressField::REGION_ID])) {
            $address[AddressField::STATE] = $this->helper()->getState($address[AddressField::REGION_ID]);
        }
        // Get street into 'street1' and 'street2' lines
        if (is_string($address['street'])) {
            $address['street'] = explode("\n", $address['street']);
        }
        if (is_null($address['street'])) {
            $address['street'] = array();
        }
        if (is_array($address['street'])) {
            for ($i=0; $i<2; $i++) {
                if (!isset($address['street'][$i])) {
                    $address['street' . ($i + 1)] = null;
                } else {
                    $address['street' . ($i + 1)] = $address['street'][$i];
                }
            }
        }

        $this->_requestAddress = $address;
        return $this->_requestAddress;
    }

    /**
     * Get requested address
     *
     * @return array
     */
    public function getRequestAddress()
    {
        return $this->_requestAddress ?: $this->_initAddress();
    }

    /**
     * Check if requested address is international
     *
     * @return bool
     */
    public function isInternational()
    {
        return array_key_exists(AddressField::COUNTRY, $this->_requestAddress)
            && $this->_requestAddress[AddressField::COUNTRY] != 'US';
    }

    /**
     * Get config value where current action is the group section
     *
     * @param $configKey
     * @return array|mixed
     */
    public function getConfigPerAction($configKey)
    {
        return $this->helper()->getConfig($configKey, $this->getRequest()->getActionName());
    }

    /**
     * Get module helper
     *
     * @return BlueAcorn_AddressValidation_Helper_Data
     */
    public function helper()
    {
        return Mage::helper('blueacorn_addressvalidation');
    }

    /**
     * Checks if error exists
     *
     * @return bool
     */
    public function hasError()
    {
        return !empty($this->_errors);
    }

    /**
     * Check if we should abort this request (try to not interfere with checkout in case of error)
     *
     * @return bool
     */
    public function getAbort()
    {
        return $this->_abort;
    }

    /**
     * Check if we should skip validation
     *
     * @return bool
     */
    public function getSkipValidation()
    {
        return $this->_skipValidation;
    }
}
