<?php
/**
 * @package     BlueAcorn\AttributeFlag
 * @version     0.1.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2015 Blue Acorn, Inc.
 */
class BlueAcorn_AttributeFlag_Model_System_Config_Source_Block
{
    const UPSELL = 'upsell';
    const CROSSSELL = 'crosssell';
    const RELATED = 'related';

    /**
     * Return all options for system config
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array(
                'label' => Mage::helper('ba_attributeflag')->__('Upsell'),
                'value' => self::UPSELL
            ),
            array(
                'label' => Mage::helper('ba_attributeflag')->__('Cross sell'),
                'value' => self::CROSSSELL
            ),
            array(
                'label' => Mage::helper('ba_attributeflag')->__('Related Products'),
                'value' => self::RELATED
            ),
        );
    }
}
