<?php
/**
 * @package     BlueAcorn\EntityMap
 * @version     0.2.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\EntityMap\Decode\Config;

use Magento\Framework\Config\ConverterInterface;

/**
 * Converts EntityMap config from \DOMDocument to array
 */
class Converter implements ConverterInterface
{
    const ENTITY_TYPE = 'type';
    const ENTITY_SCHEMA = 'schema';
    const ENTITY_KEY_MAP = 'key_map';
    const ENTITY_KEY_AGGREGATE = 'aggregate';
    const ENTITY_ATTRIBUTE_MAP = 'attribute_map';
    const ENTITY_SORTED_OPERATIONS = 'sorted_operations';

    const ATTRIBUTE_MAP_CODE = 'code';
    const ATTRIBUTE_MAP_MAPPER = 'mapper';
    const KEY_MAP_FROM = 'from';
    const KEY_MAP_TO = 'to';
    const AGGREGATE_ID = 'id';
    const AGGREGATE_COLLAPSE_KEY = 'collapse';
    const SORT_KEY = 'sort';
    const OPERATION_TYPE_KEY = 'type';

    /**
     * attribute_map attribute array [key => default_value]
     * @var array
     */
    protected $attributeMapAttributes = [
        self::ATTRIBUTE_MAP_CODE => null,
        self::ATTRIBUTE_MAP_MAPPER => null,
        self::SORT_KEY => 0
    ];

    /**
     * key_map attribute array [key => default_value]
     * @var array
     */
    protected $keyMapAttributes = [
        self::KEY_MAP_FROM => null,
        self::KEY_MAP_TO => null
    ];

    /**
     * aggregate attribute array [key => default_value]
     * @var array
     */
    protected $aggregateAttributes = [
        self::AGGREGATE_ID => null,
        self::SORT_KEY => 0
    ];

    /**
     * Convert dom node tree to array
     *
     * @param \DOMDocument $source
     * @return array
     */
    public function convert($source)
    {
        $entities = [];
        /** @var $entityNode \DOMNode */
        foreach($source->getElementsByTagName('entity') as $entityNode) {
            $entityType = $entityNode->attributes->getNamedItem('name')->nodeValue;
            $entities[$entityType] = $this->extractEntityData($entityNode);
        }

        return $entities;
    }

    /**
     * Extract entity data
     *
     * @param \DOMNode $entityNode
     * @return array
     */
    protected function extractEntityData(\DOMNode $entityNode)
    {
        $data = [
            self::ENTITY_TYPE => $entityNode->attributes->getNamedItem('name')->nodeValue,
            self::ENTITY_SCHEMA => $entityNode->attributes->getNamedItem('schema')->nodeValue,
            self::ENTITY_ATTRIBUTE_MAP => [],
            self::ENTITY_KEY_MAP => [],
            self::ENTITY_KEY_AGGREGATE => [],
        ];
        $operationId = 1;
        /** @var $childNode \DOMNode */
        foreach($entityNode->childNodes as $childNode) {
            switch($childNode->nodeName) {
                case (self::ENTITY_ATTRIBUTE_MAP):
                    $attrMapData = [self::OPERATION_TYPE_KEY => self::ENTITY_ATTRIBUTE_MAP];
                    foreach($this->attributeMapAttributes as $key => $default) {
                        $attrMapData[$key] = $childNode->attributes->getNamedItem($key)->nodeValue ?: $default;
                    }
                    $data[self::ENTITY_ATTRIBUTE_MAP][$operationId++] = $attrMapData;
                    break;
                case (self::ENTITY_KEY_MAP):
                    $keyMapData = [self::OPERATION_TYPE_KEY => self::ENTITY_KEY_MAP];
                    foreach($this->keyMapAttributes as $key => $default) {
                        $keyMapData[$key] = $childNode->attributes->getNamedItem($key)->nodeValue ?: $default;
                    }
                    $data[self::ENTITY_KEY_MAP][$operationId++] = $keyMapData;
                    break;
                case(self::ENTITY_KEY_AGGREGATE):
                    $aggregateData = [self::OPERATION_TYPE_KEY => self::ENTITY_KEY_AGGREGATE];
                    foreach($this->aggregateAttributes as $key => $default) {
                        $aggregateData[$key] = $childNode->attributes->getNamedItem($key)->nodeValue ?: $default;
                    }
                    $aggregateData[self::AGGREGATE_COLLAPSE_KEY] = [];
                    foreach($childNode->childNodes as $keyNode) {
                        if ($keyNode->nodeName == 'key') {
                            $aggregateData[self::AGGREGATE_COLLAPSE_KEY][] = $keyNode->attributes->getNamedItem('id')->nodeValue;
                        }
                    }
                    $data[self::ENTITY_KEY_AGGREGATE][$operationId++] = $aggregateData;
                    break;
            }
        }
        $data[self::ENTITY_SORTED_OPERATIONS] = $this->getSortedOperations($data);
        return $data;
    }

    /**
     * Get sorted operations
     *
     * @param array $data
     * @return array
     */
    protected function getSortedOperations(array $data)
    {
        // Key maps always come first (for now, don't see any reason these should be sorted)
        $keyMapOps = array_values($data[self::ENTITY_KEY_MAP]);
        $mixedOps = array_merge(
            array_values($data[self::ENTITY_KEY_AGGREGATE]),
            array_values($data[self::ENTITY_ATTRIBUTE_MAP])
        );
        usort($mixedOps, function($opA, $opB) {
            // Favor aggregating before mapping
            if ($opA[self::SORT_KEY] == $opB[self::SORT_KEY]) {
                if ($opA[self::OPERATION_TYPE_KEY] != $opB[self::OPERATION_TYPE_KEY]) {
                    return $opA[self::OPERATION_TYPE_KEY] == self::ENTITY_KEY_AGGREGATE
                        ? -1
                        : 1;
                }
                return 0;
            }
            return $opA[self::SORT_KEY] < $opB[self::SORT_KEY]
                ? -1
                : 1;
        });
        return array_merge($keyMapOps, $mixedOps);
    }
}
