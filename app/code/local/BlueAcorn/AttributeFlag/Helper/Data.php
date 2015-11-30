<?php
/**
 * @package     BlueAcorn\AttributeFlag
 * @version     0.1.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2015 Blue Acorn, Inc.
 */ 
class BlueAcorn_AttributeFlag_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Convenient wrapper for core date-checking method
     * Defaults to current store
     *
     * @param null|string $dateFrom
     * @param null|string $dateTo
     * @param null|int|string|Mage_Core_Model_Store $store
     * @return bool
     */
    public function isStoreDateInInterval($dateFrom = null, $dateTo = null, $store = null)
    {
        // Default to current store
        if (is_null($store)) {
            $store = Mage::app()->getStore();
        }
        return Mage::app()->getLocale()->isStoreDateInInterval($store, $dateFrom, $dateTo);
    }

    /**
     * Quick getter for attribute values that might not exist on product
     * If attribute doesn't exist for product, returns null as expected
     *
     * @param Mage_Catalog_Model_Product $product
     * @param string $attributeCode
     * @return mixed|null
     */
    public function getAttributeValue(Mage_Catalog_Model_Product $product, $attributeCode)
    {
        if ($product->hasData($attributeCode)) {
            return $product->getData($attributeCode);
        }
        $value = Mage::getResourceSingleton('catalog/product')->getAttributeRawValue(
            $product->getId(),
            $attributeCode,
            Mage::app()->getStore()->getStoreId()
        );

        return ($value !== false) ? $value : null;
    }
}