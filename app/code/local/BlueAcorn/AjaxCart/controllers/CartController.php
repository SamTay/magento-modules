<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @copyright  Copyright (c) 2013 Blue Acorn (http://www.blueacorn.com)
 * @author: Thomas Slade
 * @namespace: BlueAcorn
 * @module: AjaxCart
 *
 * @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

require_once Mage::getModuleDir('controllers', 'Mage_Checkout') . DS . 'CartController.php';

class BlueAcorn_AjaxCart_CartController extends Mage_Checkout_CartController {

    protected $_messages = array();

    /**
     * Add product to shopping cart action
     */
    public function addAction()
    {
        if(Mage::getStoreConfig('checkout/cart/redirect_to_cart')){
            parent::addAction();
        } else {
            $cart   = $this->_getCart();
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

                /**
                 * Check product availability
                 */
                if (!$product) {
                    return;
                }

                $cart->addProduct($product, $params);
                if (!empty($related)) {
                    $cart->addProductsByIds(explode(',', $related));
                }

                $cart->save();
                $this->_getSession()->setCartWasUpdated(true);

                $this->addMessage('cart_qty', $this->_getQuote()->getItemsQty());

                /**
                 * @todo remove wishlist observer processAddToCart
                 */
                Mage::dispatchEvent('checkout_cart_add_product_complete',
                    array('product' => $product, 'request' => $this->getRequest(), 'response' => $this->getResponse())
                );

                $this->addMessage('minicart_html', $this->getCartHtml());

                if (!$this->_getSession()->getNoCartRedirect(true)) {
                    if (!$cart->getQuote()->getHasError()){
                        $message = $this->__('%s was added to your shopping cart.', Mage::helper('core')->escapeHtml($product->getName()));
                        $this->addSuccess($message);
                    }
                }
            } catch (Mage_Core_Exception $e) {
                if ($this->_getSession()->getUseNotice(true)) {
                    $this->addError(Mage::helper('core')->escapeHtml($e->getMessage()));
                    $this->_getSession()->addNotice(Mage::helper('core')->escapeHtml($e->getMessage()));
                } else {
                    $messages = array_unique(explode("\n", $e->getMessage()));
                    foreach ($messages as $message) {
                        $this->addError($this->_getSession()->addError(Mage::helper('core')->escapeHtml($message)));
                    }
                }
                $url = $this->_getSession()->getRedirectUrl(true);
                if($url){
                    $this->addMessage('redirect', $url);
                }
            } catch (Exception $e) {
                $this->addError($this->__('Cannot add the item to shopping cart.'));
                Mage::logException($e);
            }

            $response = $this->getResponse()->setHeader('Content-Type', 'text/javascript');
            if(array_key_exists('error', $this->_messages)){
                $response->setHttpResponseCode(500);
            }
            $response->setBody(Zend_Json::encode($this->_messages));
        }
    }

    /**
     * Delete shoping cart item action
     */
    public function deleteAction()
    {
        $id = (int) $this->getRequest()->getParam('id');
        $noRedirect = $this->getRequest()->getParam('no-redirect');
        if ($id) {
            try {
                $this->_getCart()->removeItem($id)
                    ->save();
            } catch (Exception $e) {
                $this->_getSession()->addError($this->__('Cannot remove the item.'));
                Mage::logException($e);
            }
        }
        if($noRedirect){
            $this->getResponse()->setHttpResponseCode(200);
        } else {
            $this->_redirectReferer(Mage::getUrl('*/*'));
        }
    }

    protected function addMessage($msgType = 'error', $msg = ''){
        $this->_messages[$msgType] = $msg;
    }

    protected function addError($msg = ''){
        $this->addMessage('error', $msg);
    }

    protected function addSuccess($msg = ''){
        $this->addMessage('success', $msg);
    }

    protected function getCartHtml(){
        $html = Mage::app()->getLayout()->createBlock('checkout/cart_sidebar')->setTemplate('checkout/cart/cartheader.phtml')->toHtml();
        $html = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $html);
        return $html;
    }
}