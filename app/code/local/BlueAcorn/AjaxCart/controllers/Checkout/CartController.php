<?php
require_once Mage::getModuleDir('controllers', 'Mage_Checkout') . DS . 'CartController.php';
class BlueAcorn_AjaxCart_Checkout_CartController extends Mage_Checkout_CartController
{
    /**
     * Hold messages to be returned as JSON object
     * @var array
     */
    protected $_messages = array();

    /**
     * Extend core addAction to ajaxify the process
     *
     * Add product to shopping cart action
     *
     * @return Mage_Core_Controller_Varien_Action
     * @throws Exception
     */
    public function addAction()
    {
        // If the ajax cart is not enabled, default to parent addAction (non ajax)
        if (!Mage::getStoreConfig('blueacorn_ajaxcart/general/enabled')) {
            parent::addAction();
        }

        // If form key is invalid, tell the customer to try again (because we are reloading the page for them).
        if (!$this->_validateFormKey()) {
            $this->addError('Your session expired. Please try again.');
            $this->sendJsonResponse();
            return;
        }
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

            /**
             * Check product availability (i.e., can be loaded for current store)
             * If unavailable, reload page with session error
             */
            if (!$product) {
                $this->addError('Sorry, this product is not available.');
                $this->sendJsonResponse();
                return;
            }

            $cart->addProduct($product, $params);
            if (!empty($related)) {
                $cart->addProductsByIds(explode(',', $related));
            }

            $cart->save();
            $this->_getSession()->setCartWasUpdated(true);

            Mage::dispatchEvent('checkout_cart_add_product_complete',
                array('product' => $product, 'request' => $this->getRequest(), 'response' => $this->getResponse())
            );

            $this->addMessage('minicart_html', $this->getMinicartHtml());
            $this->addMessage('success',
                $this->__('%s was added to your shopping cart.', Mage::helper('core')->escapeHtml($product->getName()))
            );

        } catch (Mage_Core_Exception $e) {
            // Add Mage Exception messages as session notices or session errors
            if ($this->_getSession()->getUseNotice(true)) {
                $this->addError(Mage::helper('core')->escapeHtml($e->getMessage()), 'Notice');
            } else {
                $messages = array_unique(explode("\n", $e->getMessage()));
                foreach ($messages as $message) {
                    $this->addError(Mage::helper('core')->escapeHtml($message));
                }
            }

            // If session has redirect_url, use this on the frontend. Otherwise redirect to cart.
            $url = $this->_getSession()->getRedirectUrl(true);
            if ($url) {
                $this->addMessage('redirect_url', $url);
            } else {
                $this->addMessage('redirect_url', Mage::helper('checkout/cart')->getCartUrl());
            }
        } catch (Exception $e) {
            // For hardcore exceptions, log and add exception to session.
            // By default, this will cause a page reload (not explicitly setting redirect_url)
            $this->addError($this->__('Cannot add the item to shopping cart.'), 'Exception');
            Mage::logException($e);
        }

        $this->sendJsonResponse();
    }

    /**
     * Set response body to json encoded messages
     * @throws Zend_Controller_Response_Exception
     */
    protected function sendJsonResponse()
    {
        $response = $this->getResponse()->setHeader('Content-Type', 'application/json');
        if ($this->hasError()) {
            $response->setHttpResponseCode(500);
        }

        $response->setBody(Zend_Json::encode($this->_messages));
    }

    /**
     * Add message to JSON response
     * @param $msgType
     * @param $msg
     */
    protected function addMessage($msgType, $msg)
    {
        $this->_messages[$msgType] = $msg;
    }

    /**
     * Generate minicart block HTML to update frontend
     * @return mixed|string
     */
    protected function getMinicartHtml()
    {
        $this->loadLayout();
        $html = $this->getLayout()->getBlock('minicart_head')->toHtml();
        $html = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $html);
        return $html;
    }

    /**
     * Add message of type error to JSON response
     * Add message to session for the next page load
     *
     * @param bool|string $url
     * @param $msg
     */
    protected function addError($msg, $type = 'Error')
    {
        $addMethod = 'add' . $type;
        $this->_getSession()->$addMethod($msg);
        $this->addMessage('error', true);
    }

    /**
     * Check if error exists already
     * @return bool
     */
    protected function hasError()
    {
        if (array_key_exists('error', $this->_messages)) {
            return true;
        }
        return false;
    }
}