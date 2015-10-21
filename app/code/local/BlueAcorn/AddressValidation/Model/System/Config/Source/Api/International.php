<?php
/**
 * @package     BlueAcorn\AddressValidation
 * @version     0.1.0
 * @author      Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2015 Blue Acorn, Inc.
 */
class BlueAcorn_AddressValidation_Model_System_Config_Source_Api_International
{
    const STRIKEIRON = 'strikeiron';

    /**
     * Get sys config options for Presentation select
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array(
                'label' => Mage::helper('blueacorn_addressvalidation')->__('StrikeIron'),
                'value' => self::STRIKEIRON
            )
        );
    }

    /**
     * Get the text labels for given values
     *
     * @param $values
     * @param bool $string
     * @return array|string
     */
    public function getOptionText($values, $string = false)
    {
        $options = $this->toOptionArray();
        if (!is_array($values)) {
            $values = explode(',', $values);
        }

        $texts = array();
        foreach($values as $value) {
            if (isset($options[$value])) {
                $texts[] = $options[$value];
            }
        }

        return ($string) ? implode(',', $texts) : $texts;
    }
}
