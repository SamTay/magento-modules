<?php
/**
 * @package     BlueAcorn\AjaxCart
 * @version
 * @author      Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2015 Blue Acorn, Inc.
 */
require_once Mage::getModuleDir('controllers', 'Mage_Checkout') . DS . 'CartController.php';

/**
 * Class BlueAcorn_AjaxCart_Checkout_CartController
 *
 * Extends Mage_CartController to ajaxify the addAction
 */
class BlueAcorn_AjaxCart_Checkout_CartController extends Mage_Checkout_CartController
{
    /**
     * Extend to check if we should refer to ajax action
     * Add product to shopping cart action
     *
     * @return Mage_Core_Controller_Varien_Action
     * @dispatches ba_ajax_addtocart_send_response_before
     * @throws Exception
     */
    public function addAction()
    {
        if ($this->getRequest()->isAjax() && Mage::getStoreConfig('blueacorn_ajaxcart/general/enabled')) {
            return $this->ajaxAddAction();
        }
        parent::addAction();
    }

    /**
     * Extend core addAction to ajaxify the process
     * Adhere to practices found in ajaxUpdateAction and ajaxDeleteAction
     */
    public function ajaxAddAction()
    {
        if (!$this->_validateFormKey()) {
            Mage::throwException('Invalid form key');
        }

        // Initialize params and models
        $result = new Varien_Object();
        $cart = $this->_getCart();
        $params = $this->getRequest()->getParams();
        try {
            if (isset($params['qty'])) {
                $filter = new Zend_Filter_LocalizedToNormalized(
                    array('locale' => Mage::app()->getLocale()->getLocaleCode())
                );
                $params['qty'] = $filter->filter($params['qty']);
            }
            $product = $this->_initProduct();
            $related = $this->getRequest()->getParam('related_product');
            if (!$product) {
                Mage::throwException($this->__('Sorry, this product is not available.'));
            }

            // Add product to cart
            $cart->addProduct($product, $params);
            if (!empty($related)) {
                $cart->addProductsByIds(explode(',', $related));
            }
            $cart->save();
            $this->_getSession()->setCartWasUpdated(true);
            Mage::dispatchEvent('checkout_cart_add_product_complete',
                array('product' => $product, 'request' => $this->getRequest(), 'response' => $this->getResponse())
            );

            // Load layout and add response data
            $this->loadLayout();
            $result->setContent($this->getLayout()->getBlock('minicart_content')->toHtml())
                ->setQty($this->_getCart()->getSummaryQty())
                ->setMessage($this->__('%s was added to your shopping cart.', $product->getName()))
                ->setSuccess(1);
        } catch (Mage_Core_Exception $e) {
            if ($this->_getSession()->getUseNotice(true)) {
                $result->setNotice($e->getMessage());
            } else {
                $messages = array_unique(explode("\n", $e->getMessage()));
                $result->setError(implode('<br>', $messages));
            }
            $result->setSuccess(0);
        } catch (Exception $e) {
            $result->setError($this->__('Cannot add the item to shopping cart.'));
            $result->setSuccess(0);
            Mage::logException($e);
        }

        // Dispatch event for further customizations and send response
        Mage::dispatchEvent('ba_ajax_addtocart_send_response_before', array(
            'controller' => $this,
            'response_data' => $result
        ));
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }
}