<?php
require_once "Mage/Checkout/controllers/CartController.php";

class BlueAcorn_AjaxCart_CartController extends Mage_Checkout_CartController
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
        if (!Mage::getStoreConfig('blueacorn_ajaxcart/general/enabled')) {
            parent::addAction();
        }

        if (!$this->_validateFormKey()) {
            $this->addError('Invalid form key. Try reloading the page.');
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

            if (!$this->_getSession()->getNoCartRedirect(true)) {
                if (!$cart->getQuote()->getHasError()) {
                    $message = $this->__('%s was added to your shopping cart.', Mage::helper('core')->escapeHtml($product->getName()));
                    $this->addMessage('success', $message);
                }
            }
        } catch (Mage_Core_Exception $e) {
            if ($this->_getSession()->getUseNotice(true)) {
                $this->addError(Mage::helper('core')->escapeHtml($e->getMessage()));
                $this->_getSession()->addNotice(Mage::helper('core')->escapeHtml($e->getMessage()));
            } else {
                $messages = array_unique(explode("\n", $e->getMessage()));
                foreach ($messages as $message) {
                    $this->addError(Mage::helper('core')->escapeHtml($message));
                }
            }

            $url = $this->_getSession()->getRedirectUrl(true);
            if ($url) {
                $this->getResponse()->setRedirect($url);
            } else {
                $this->_redirectReferer(Mage::helper('checkout/cart')->getCartUrl());
            }
        } catch (Exception $e) {
            $this->addError($this->__('Cannot add the item to shopping cart.'));
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
    protected function addError($msg, $url = false)
    {
        $url = ($url) ?: true;
        $this->_getSession()->addError($msg);
        $this->addMessage('error', $url);
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