<?php
/**
 * @package     BlueAcorn\EntityMap
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\EntityMap;

interface MapperInterface
{
    /**
     * Maps [inputKey, inputValue] to an [outputKey, outputValue]
     *
     * @param string $key
     * @param mixed $value
     * @return array
     */
    public function map($key, $value);
}
