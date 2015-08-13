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
     * Holds whether or not to abort this validation request (response with http code 500)
     * @var bool
     */
    protected $_abort = false;

    /**
     * Holds original address request (with street1 and street2 keys converted)
     * @var array
     */
    protected $_requestAddress = array();

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
        $address = $this->getRequestAddress();
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
                $this->_abort = true;
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
        $response = new Varien_Object();

        if ($result->hasAddress()) {
            $response['addresses'] = $result->getAddresses();
        } elseif ($this->_getAbort()) {
            $this->getResponse()->setHttpResponseCode(500);
            return;
        }
        // Dispatch event for further customization of response
        Mage::dispatchEvent('ba_addressvalidation_send_response_before', array(
            'controller' => $this,
            'response' => $response
        ));

        // Determine whether this is a modal or checkout step
        if ($response->getForm() || $response->getError()) {
            $presentation = Mage::helper('blueacorn_addressvalidation')->getConfig('presentation', 'checkout');
            $isModal = ($presentation == BlueAcorn_AddressValidation_Model_System_Config_Source_Presentation::MODAL);
            $response->setIsModal($isModal);
        }

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
        // Intentional assignment - check for user selecting saved shipping address
        if ($id = $this->getRequest()->getPost('shipping_address_id')) {
            $request = Mage::getModel('customer/address')->load($id)->getData();
            $address['entity_id'] = $id;
        // Otherwise, they must have filled out the full shipping form
        } else {
            $request = $this->getRequest()->getParam('shipping');
        }

        // Sanitize request
        foreach($this->_addressFields as $field) {
            $address[$field] = isset($request[$field]) ? $request[$field] : null;
        }
        if (!is_null($address['region_id'])) {
            $address['state'] = Mage::helper('blueacorn_addressvalidation')->getState($address['region_id']);
        }
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
        return $this->_abort;
    }


}
