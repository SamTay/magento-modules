<?php
/**
 * @package     BlueAcorn\AttributeFlag
 * @version     0.1.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2015 Blue Acorn, Inc.
 */
class BlueAcorn_AttributeFlag_Model_System_Config_Source_Page
{
    const PRODUCT = 'product';
    const CATEGORY = 'category';
    const CART = 'cart';
    const CHECKOUT = 'checkout';

    /**
     * Return all options for system config
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array(
                'label' => Mage::helper('ba_attributeflag')->__('Product'),
                'value' => self::PRODUCT
            ),
            array(
                'label' => Mage::helper('ba_attributeflag')->__('Category'),
                'value' => self::CATEGORY
            ),
            array(
                'label' => Mage::helper('ba_attributeflag')->__('Cart'),
                'value' => self::CART
            ),
            array(
                'label' => Mage::helper('ba_attributeflag')->__('Checkout'),
                'value' => self::CHECKOUT
            )
        );
    }
}