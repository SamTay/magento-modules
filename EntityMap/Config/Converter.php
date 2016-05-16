<?php
/**
 * @package     BlueAcorn\EntityMap
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\EntityMap\Config;

use Magento\Framework\Config\ConverterInterface;

/**
 * Converts EntityMap config from \DOMDocument to array
 */
class Converter implements ConverterInterface
{
    //TODO Constants for XML elements/attributes

    /**
     * Convert dom node tree to array
     *
     * @param \DOMDocument $source
     * @return array
     */
    public function convert($source)
    {
        //TODO Conversions
    }
}
