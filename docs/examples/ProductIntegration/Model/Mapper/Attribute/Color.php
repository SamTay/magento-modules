<?php
/**
 * @package     BlueAcorn\ProductIntegration
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\ProductIntegration\Model\Mapper\Attribute;

use BlueAcorn\EntityMap\MapperInterface;

class Color implements MapperInterface
{
    /**
     * {@inheritdoc}
     */
    public function map($key, $value)
    {
        $value = preg_replace('/\s+/', '', $value);
        $colorStrings = explode(',', $value);
        $optionIds = array_reduce($colorStrings, function($carry, $colorString) {
            return $carry . $this->getColorOptionId($colorString);
        }, '');

        return [$key, $optionIds];
    }

    /**
     * Dummy method, pretend it gets color option ID from label
     *
     * @param $colorString
     * @return int
     */
    protected function getColorOptionId($colorString)
    {
        // Do some look up
        return (int)$colorString;
    }
}