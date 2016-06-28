<?php
/**
 * @package     BlueAcorn\EntityMap
 * @version     0.2.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\EntityMap\Model;

interface ConverterInterface
{
    /**
     * Convert data/entity-type into formatted array, based on entity_map_*.xml configuration
     *
     * @param array $data
     * @param string $entityType
     * @return array
     */
    public function convert(array $data, $entityType);
}
