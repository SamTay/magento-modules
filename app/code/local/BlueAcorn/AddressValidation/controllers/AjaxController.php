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
            $shortname = 'blueacorn_addressvalidation/api/' . $api;
            try {
                $apiResult = Mage::getModel($shortname)->validateAddress($address);
            } catch (Mage_Api_Exception $e) {
                // Log and handle exception

                if ($e->getCode() == BlueAcorn_AddressValidation_Model_ApiInterface::RESPONSE_ERROR) {
                    // TODO: This text needs to be configurable!
                    $this->_errors[] = ucfirst($api) . ' was unable to verify this address.';
                }
            }

            if ($apiResult) {
                $result = $result->merge($apiResult);
            }
        }

        $this->_sendResponse($result);
    }

    protected function _sendResponse(BlueAcorn_AddressValidation_Model_Result $result)
    {
        $response = array();

        if ($result->hasAddress() || $result->hasMessage()) {
            $response['addresses'] = $result->getAddresses();
            $response['messages'] = $result->getMessages();
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
     * @return array
     */
    protected function _initAddress()
    {
        $address = array();
        foreach($this->_addressFields as $field) {
            $address[$field] = $this->getRequest()->getParam($field);
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


}
