<?php
/**
 * @package     BlueAcorn\AddressValidation
 * @version     0.1.0
 * @author      Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2015 Blue Acorn, Inc.
 */
class BlueAcorn_AddressValidation_AjaxController extends Mage_Core_Controller_Front_Action
{
    /**
     * Array to hold possible errors
     * @var array
     */
    protected $_errors = array();

    /**
     * The fields that make up the request address
     * @var array
     */
    protected $_addressFields = array(
        'street',
        'city',
        'region_id',
        'postcode'
    );

    /**
     * Default action validates against enabled APIs and returns validated address and/or message
     */
    public function indexAction()
    {
        $address = $this->_initAddress();
        $result = Mage::getModel('blueacorn_addressvalidation/result');
        foreach(Mage::helper('blueacorn_addressvalidation')->getEnabledApis() as $api) {
            $apiResult = null;
            $shortname = 'blueacorn_addressvalidation/api_' . $api;
            try {
                $apiResult = Mage::getModel($shortname)->validateAddress($address);
            } catch (Mage_Api_Exception $e) {
                Mage::helper('blueacorn_addressvalidation')->log(
                    $e->getCustomMessage(),
                    $e->getCode(),
                    $api
                );
                if ($e->getCode() == BlueAcorn_AddressValidation_Model_ApiInterface::RESPONSE_ERROR) {
                    // TODO: This text needs to be configurable!
                    $this->_errors[] = ucfirst($api) . ' was unable to verify this address.';
                }
            } catch (Exception $e) {
                Mage::helper('blueacorn_addressvalidation')->log(
                    $e->getMessage(),
                    null,
                    $api
                );
                /**
                 * If this is not an API exception, this is not simply an issue with verifying an address,
                 * but rather an internal error/bug, hence the error will not help the customer.
                 * Instead, continue checkout by default.
                 */
                $this->_errors['abort'] = true;
            }

            if ($apiResult) {
                $result = $result->merge($apiResult);
            }
        }

        $this->_sendResponse($result);
    }

    /**
     * Send response to browser, but dispatch event first for further customization of response
     *
     * @param BlueAcorn_AddressValidation_Model_Result $result
     * @throws Zend_Controller_Response_Exception
     */
    protected function _sendResponse(BlueAcorn_AddressValidation_Model_Result $result)
    {
        $response = array();

        if ($result->hasAddress() || $result->hasMessage()) {
            $response['addresses'] = $result->getAddresses();
            $response['messages'] = $result->getMessages();
        } elseif ($this->_getAbort()) {
            $this->getResponse()->setHttpResponseCode(500);
            return;
        }
        if ($this->_hasError()) {
            $response['errors'] = $this->_errors;
        }

        // Dispatch event for further customization of response
        Mage::dispatchEvent('ba_addressvalidation_send_response_before', array(
            'controller' => $this,
            'response' => $response
        ));

        $this->getResponse()->setHttpResponseCode(200)
            ->setHeader('Content-Type', 'application/json')
            ->setBody(Zend_Json::encode($response));
    }

    /**
     * Return a well formulated address array from the request
     *
     * @return array
     */
    protected function _initAddress()
    {
        $address = array();
        $request = $this->getRequest()->getParam('shipping');
        foreach($this->_addressFields as $field) {
            $address[$field] = isset($request[$field]) ? $request[$field] : null;
        }
        if (is_null($address['street'])) {
            $address['street'] = array();
        }
        for ($i=0; $i<2; $i++) {
            if (!isset($address['street'][$i])) {
                $address['street'][$i] = null;
            }
        }

        return $address;
    }

    /**
     * Checks if error exists
     *
     * @return bool
     */
    protected function _hasError()
    {
        return !empty($this->_errors);
    }

    /**
     * Check if we should abort this request (try to not interfere with checkout in case of error)
     *
     * @return bool
     */
    protected function _getAbort()
    {
        return array_key_exists('abort', $this->_errors) && $this->_errors['abort'];
    }


}
