<?php
/**
 * @package     BlueAcorn\AttributeFlag
 * @version     0.1.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2015 Blue Acorn, Inc.
 */ 
class BlueAcorn_AttributeFlag_Helper_Data extends Mage_Core_Helper_Abstract
{
    const SYSTEM_CONFIG_SECTION = 'ba_attributeflag';
    const XML_PATH_FLAGS = 'global/ba_attributeflags';

    /**
     * Holds currently enabled flags
     * @var array
     */
    protected $_flags = array();

    /**
     * Holds sys config section
     * @var array
     */
    protected $_config = array();

    /**
     * Default group for using getConfig with single field argument
     * @var string
     */
    protected $_defaultGroup = 'general';

    /**
     * Get flag associated with $product
     *
     * @param Mage_Catalog_Model_Product $product
     * @return Varien_Object[]
     */
    public function getFlags(Mage_Catalog_Model_Product $product)
    {
        $applicableFlags = array();
        $max = $this->getConfig('max_flags');

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
        $flagIds = $this->getConfigMultiselect('flags_enabled');
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
     * My signature getConfig helper method
     *
     * @param bool $field
     * @param bool $group
     * @return array|mixed
     */
    public function getConfig($field = false, $group = false)
    {
        if (empty($this->_config)) {
            $this->_config = Mage::getStoreConfig(self::SYSTEM_CONFIG_SECTION);
        }
        // If called with no arguments, give full 2d array
        if (!$field && !$group) {
            return $this->_config;
        }
        $group = $group ?: $this->_defaultGroup;
        if (!array_key_exists($group, $this->_config)) {
            return null;
        }
        // If you want a field
        if ($field && array_key_exists($field, $this->_config[$group])) {
            return $this->_config[$group][$field];
        }
        // If you want a group
        if (!$field && $group) {
            return $this->_config[$group];
        }
        return null;
    }

    /**
     * Wrapper for getting multiselect value from system config
     *
     * @param bool $field
     * @param bool $group
     * @return array
     */
    public function getConfigMultiselect($field = false, $group = false)
    {
        $stringValue = $this->getConfig($field, $group);
        if (empty($stringValue)) {
            return array();
        }
        return explode(',', $stringValue);
    }

    /**
     * Check if module is enabled in system config
     *
     * @return bool
     */
    public function isEnabled()
    {
        return $this->getConfig('module_enabled');
    }
}