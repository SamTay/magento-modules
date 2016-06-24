<?php
/**
 * @package     BlueAcorn\EntityMap
 * @version     0.2.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\EntityMap;

class Escape
{
    const DEFAULT_ESCAPE_CHAR = '\\';
    const PLACEHOLDER = 'PLACEHOLDER';

    /**
     * Explode a string, taking into account escaped delimiters
     *
     * @param string $string
     * @param string $delimiter
     * @param string $escape
     * @param bool $removeWhitespace
     * @return array
     */
    static public function _explode(
        $string,
        $delimiter = ',',
        $escape = self::DEFAULT_ESCAPE_CHAR,
        $removeWhitespace = true
    ) {
        // Temporarily replace escaped delimiters
        $string = str_replace($escape . $delimiter, self::PLACEHOLDER, $string);
        // First remove any spaces after the $delimiter
        if ($removeWhitespace) {
            $pattern = "/$delimiter\s+/";
            $string = preg_replace($pattern, $delimiter, $string);
        }
        $array = explode($delimiter, $string);
        // Replace placeholder back with delimiter (without escape char!)
        return array_map(function($item) use ($delimiter) {
            return str_replace(self::PLACEHOLDER, $delimiter, $item);
        }, $array);
    }

    /**
     * Implode an array, taking into account escaped delimiters
     *
     * @param array $array
     * @param string $glue
     * @param string $escape
     * @return string
     */
    static public function _implode(
        array $array,
        $glue = ',',
        $escape = self::DEFAULT_ESCAPE_CHAR
    ) {
        foreach($array as &$string) {
            $string = str_replace($escape . $glue, $glue, $string); // Don't double escape pre-escaped chars
            $string = str_replace($glue, $escape . $glue, $string);
        }
        return implode($glue, $array);
    }

}
