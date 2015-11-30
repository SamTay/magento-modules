<?php
/**
 * @package     BlueAcorn\AttributeFlag
 * @version     0.1.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2015 Blue Acorn, Inc.
 */
class BlueAcorn_AttributeFlag_Model_System_Config_Source_Flag
{
    const FLAG_INTERFACE = 'BlueAcorn_AttributeFlag_Model_FlagInterface';
    const XML_PATH_FLAGS = 'global/ba_attributeflags';

    /**
     * Gathers all flag options - filters out disabled flags and
     * any flags that do not implement required interface
     *
     * @return array
     */
    public function toOptionArray()
    {
        $flags = Mage::getConfig()->getNode(self::XML_PATH_FLAGS);
        $options = array();
        foreach($flags->children() as $id => $flag) {
            if ($flag->is('disabled')) {
                continue;
            }
            $flagClassName = $flag->getClassName();
            if (!is_a($flagClassName, self::FLAG_INTERFACE, true)) {
                continue;
            }
            $options[] = array(
                'label' => Mage::helper('ba_attributeflag')->__($flag->label->__toString()),
                'value' => $id
            );
        }

        return $options;
    }
}