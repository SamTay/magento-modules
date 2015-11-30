<?php
/**
 * @package     BlueAcorn\AttributeFlag
 * @version     0.1.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2015 Blue Acorn, Inc.
 */
abstract class BlueAcorn_AttributeFlag_Model_FlagAbstract
{
    const TYPE_DATE_RANGE = 'TYPE_DATE_RANGE';
    const TYPE_VALUE_TRUTHY = 'TYPE_VALUE_TRUTHY';

    /**
     * Holds attribute type for descendant classes
     * @var
     */
    protected $_attributeType;

    /**
     * Holds attribute code for checking value not null/false/etc.
     * @var
     */
    protected $_attribute;

    /**
     * Holds attribute code for date range
     * @var
     */
    protected $_dateStartAttribute;

    /**
     * Holds attribute code for date range
     * @var
     */
    protected $_dateEndAttribute;

    /**
     * Checks whether flag applies depending on $this->_attributeType
     *
     * @param Mage_Catalog_Model_Product $product
     * @return bool
     */
    public function validate(Mage_Catalog_Model_Product $product)
    {
        switch ($this->_attributeType) {
            case (self::TYPE_DATE_RANGE):
                return $this->_validateDates($product);
                break;
            case (self::TYPE_VALUE_TRUTHY):
                return $this->_validateTruthy($product);
                break;
            default:
                return false;
        }
    }

    /**
     * Validates flag based on start/end date attributes
     *
     * @param Mage_Catalog_Model_Product $product
     * @return bool
     */
    protected function _validateDates(Mage_Catalog_Model_Product $product)
    {
        $helper = Mage::helper('ba_attributeflag');
        return $helper->isStoreDateInInterval(
            $helper->getAttributeValue($product, $this->_dateBeforeAttribute),
            $helper->getAttributeValue($product, $this->_dateEndAttribute)
        );
    }

    /**
     * Validates flag based on single attribute value being "truthy"
     *
     * @param Mage_Catalog_Model_Product $product
     * @return bool
     */
    protected function _validateTruthy(Mage_Catalog_Model_Product $product)
    {
        return (bool) Mage::helper('ba_attributeflag')->getAttributeValue($product, $this->_attribute);
    }
}