<?php
/**
 * @package     BlueAcorn\AttributeFlag
 * @version     0.1.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2015 Blue Acorn, Inc.
 */
class BlueAcorn_AttributeFlag_Model_Flag_Sale
    extends BlueAcorn_AttributeFlag_Model_FlagAbstract
    implements BlueAcorn_AttributeFlag_Model_FlagInterface
{
    /**
     * Sets date properties for parent methods
     */
    public function __construct()
    {
        $this->_attributeType = self::TYPE_DATE_RANGE;
        $this->_dateStartAttribute = 'special_from_date';
        $this->_dateEndAttribute = 'special_to_date';
    }

    /**
     * Get flag description
     *
     * @return string
     */
    public function getDescription()
    {
        return 'Applies to products that have an active special price.';
    }
}