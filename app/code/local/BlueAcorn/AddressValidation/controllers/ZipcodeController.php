<?php
/**
 * @package     BlueAcorn\AddressValidation
 * @version     0.2.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2015 Blue Acorn, Inc.
 */
class BlueAcorn_AddressValidation_ZipcodeController extends Mage_Core_Controller_Front_Action
{
    /**
     * Lookup zipcode action to be called via ajax
     * Returns json response
     */
    public function lookupAction()
    {
        $zipcode = $this->getRequest()->getParam('postcode');
        try {
            $response = Mage::getSingleton('blueacorn_addressvalidation/zipcode_api_usps')->lookupZipcode($zipcode);
        } catch (Mage_Api_Exception $e) {
            $this->_helper()->log($e->getCustomMessage(), $e->getCode(), 'Usps');
        } catch (Exception $e) {
            $this->_helper()->log($e->getMessage(), null, 'Usps');
        }
        $this->_sendResponse(isset($response) ? $response : array());
    }

    /**
     * Set json response
     *
     * @param $response
     * @throws Zend_Controller_Response_Exception
     */
    protected function _sendResponse($response)
    {
        $this->getResponse()->setHttpResponseCode(200)
            ->setHeader('Content-Type', 'application/json')
            ->setBody(Mage::helper('core')->jsonEncode($response));
    }

    /**
     * Get module helper
     *
     * @return BlueAcorn_AddressValidation_Helper_Data
     */
    protected function _helper()
    {
        return Mage::helper('blueacorn_addressvalidation');
    }
}