<?php
/**
 * @package     BlueAcorn\AddressValidation
 * @version     0.2.0
 * @author      Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2015 Blue Acorn, Inc.
 */
class BlueAcorn_AddressValidation_Model_Observer
{
    /**
     * Observes ba_addressvalidation_send_response_before to add form html
     * to select validated addresses
     *
     * @param Varien_Event_Observer $observer
     */
    public function addFormHtml(Varien_Event_Observer $observer)
    {
        $response = $observer->getResponse();
        if (empty($response->getAddresses())) {
            return;
        }

        // Add form HTML
        $html = Mage::app()->getLayout()->createBlock('core/template')
            ->setData('addresses', $response->getAddresses())
            ->setData('original_address', $observer->getController()->getRequestAddress())
            ->setTemplate('blueacorn/addressvalidation/form.phtml')
            ->toHtml();
        $response->setForm($html);
    }

    /**
     * Observes ba_addressvalidation_send_response_before to add possible error message
     *
     * @param Varien_Event_Observer $observer
     */
    public function addErrorHtml(Varien_Event_Observer $observer)
    {
        $response = $observer->getResponse();
        $helper = Mage::helper('blueacorn_addressvalidation');
        if (!empty($response->getAddresses()) || !$response->getDisplayErrors()) {
            return;
        }
        // Get error message from sys config
        $errorMessage = $helper->getConfig('error_message', 'checkout');

        // Add error HTML to response
        $html = Mage::app()->getLayout()->createBlock('core/template')
            ->setData('error_message', $errorMessage)
            ->setTemplate('blueacorn/addressvalidation/error.phtml')
            ->toHtml();
        $response->setError($html);
    }
}
