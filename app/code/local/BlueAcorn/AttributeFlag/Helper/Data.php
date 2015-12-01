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
     * @return Varien_Object
     */
    public function getFlag(Mage_Catalog_Model_Product $product)
    {
        $enabledFlags = $this->getEnabledFlags();
        // TODO: Sort based on config
        foreach($enabledFlags as $flag) {
            if ($flag->getModel()->validate($product)) {
                // TODO: Add system config options to flag such as text/css for frontend
                return $flag;
            }
        }
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
     * @todo Finish adding flag data
     * @return array
     */
    public function getEnabledFlags()
    {
        if ($this->_flags) {
            return $this->_flags;
        }
        $flagIds = $this->getMultiselectSysConfig(self::SYSTEM_CONFIG_FLAGS_ENABLED);
        foreach($flagIds as $flagId) {
            $flag = Mage::getConfig()->getNode(self::XML_PATH_FLAGS . '/' . $flagId);
            $this->_flags[$flagId] = new Varien_Object(array(
                'model' => Mage::getSingleton($flag->model->__toString())
                // TODO: Add other stuff from sys config?
            ));
        }

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