<?php
/**
 * @package     BlueAcorn\EntityMap
 * @version     0.2.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\EntityMap;

interface MapperInterface
{
    /**
     * Maps (inputKey, inputValue) to an [outputKey => outputValue]
     * Note: can also return multiple outputKey => outputValue pairs in return array. Return value should be acceptable as
     * arguments to \Magento\Framework\DataObject::addData
     *
     * @param string $key
     * @param mixed $value
     * @return array
     */
    public function map($key, $value);
}
