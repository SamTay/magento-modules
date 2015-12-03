<?php
/**
 * @package     BlueAcorn\AttributeFlag
 * @version     0.1.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2015 Blue Acorn, Inc.
 */ 
class BlueAcorn_AttributeFlag_Helper_Data extends Mage_Core_Helper_Abstract
{
    const SYSTEM_CONFIG_MODULE_ENABLED = 'ba_attributeflag/general/module_enabled';
    const SYSTEM_CONFIG_FLAGS_ENABLED = 'ba_attributeflag/general/flags_enabled';
    const SYSTEM_CONFIG_MAX_FLAGS = 'ba_attributeflag/general/max_flags';
    const XML_PATH_FLAGS = 'global/ba_attributeflags';

    /**
     * Holds currently enabled flags
     * @var array
     */
    protected $_flags = array();

    /**
     * Get flag associated with $product
     *
     * @param Mage_Catalog_Model_Product $product
     * @return Varien_Object[]
     */
    public function getFlags(Mage_Catalog_Model_Product $product)
    {
        $applicableFlags = array();
        $max = Mage::getStoreConfig(self::SYSTEM_CONFIG_MAX_FLAGS);

        $enabledFlags = $this->getEnabledFlags();
        foreach($enabledFlags as $id => $flag) {
            if ($flag->getModel()->validate($product)) {
                $applicableFlags[$id] = $flag;
            }
            if (count($applicableFlags) == $max) {
                break;
            }
        }

        return $applicableFlags;
    }

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

    /**
     * Get all flags enabled via system config, load models and other flag info
     *
     * @return Varien_Object[]
     */
    public function getEnabledFlags()
    {
        if ($this->_flags) {
            return $this->_flags;
        }
        $flagIds = $this->getMultiselectSysConfig(self::SYSTEM_CONFIG_FLAGS_ENABLED);
        foreach($flagIds as $flagId) {
            $flag = Mage::getConfig()->getNode(self::XML_PATH_FLAGS . '/' . $flagId)->asCanonicalArray();
            $flag['model'] = (array_key_exists('model', $flag))
                ? Mage::getSingleton($flag['model'])
                : null;
            $this->_flags[$flagId] = new Varien_Object($flag);
        }

        // Prioritize flags based on <sort> node
        usort($this->_flags, function($flagA, $flagB) {
            $sortA = $flagA->getSort() ?: 0;
            $sortB = $flagB->getSort() ?: 0;
            return $sortA - $sortB;
        });

        return $this->_flags;
    }

    /**
     * Wrapper for getting multiselect value from sys config
     *
     * @param string $path
     * @return array
     */
    public function getMultiselectSysConfig($path = '')
    {
        $stringValue = Mage::getStoreConfig($path);
        if (empty($stringValue)) {
            return array();
        }
        return explode(',', $stringValue);
    }
}