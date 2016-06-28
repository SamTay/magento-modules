<?php
/**
 * @package     BlueAcorn\ProductIntegration
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\ProductIntegration\Model\Mapper\Attribute;

use BlueAcorn\EntityMap\Model\MapperInterface;

class ConfidenceBuilder implements MapperInterface
{
    const ATTRIBUTE_CODE = 'confidence_builder';

    /**
     * {@inheritdoc}
     */
    public function map($key, $value)
    {
        $this->validateKey($key);
        $values = [];
        foreach($value as $option => $flag) {
            if ($flag) {
                $values[] = $this->getOptionId($option);
            }
        }

        return [$key, implode(',', $values)];
    }

    /**
     * Get option id from string code
     *
     * @param $optionString
     * @return int
     */
    protected function getOptionId($optionString)
    {
        // Dummy method
        // Pretend this maps 'money_back_guarantee' to a custom source model value such as (int)2
        return (int)$optionString;
    }

    /**
     * Validate attribute code
     *
     * @param $key
     * @throws \LogicException
     */
    private function validateKey($key)
    {
        if ($key !== self::ATTRIBUTE_CODE) {
            throw new \LogicException(__(
                "%class should not be used for attributes other than %code",
                ['class' => get_class($this), 'code' => self::ATTRIBUTE_CODE]
            ));
        }
    }
}
