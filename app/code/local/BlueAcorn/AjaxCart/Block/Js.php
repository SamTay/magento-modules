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

class BlueAcorn_AjaxCart_Block_Js extends Mage_Core_Block_Template {

    public function useAjax(){
        return !Mage::getStoreConfig('checkout/cart/redirect_to_cart');
    }

} 