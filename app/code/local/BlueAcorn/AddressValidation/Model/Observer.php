<?php
/**
 * @package     BlueAcorn\AddressValidation
 * @version     0.1.0
 * @author      Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2015 Blue Acorn, Inc.
 */
class BlueAcorn_AddressValidation_Model_Observer
{
    public function addFormHtml(Varien_Event_Observer $observer)
    {
        $response = $observer->getResponse();
        if (empty($response->getAddresses())) {
            return;
        }

        $html = Mage::app()->getLayout()->createBlock('core/template')->setData('addresses', $response->getAddresses())
            ->setTemplate('blueacorn/addressvalidation/form.phtml')
            ->toHtml();

        $response->setForm($html);
    }
}
