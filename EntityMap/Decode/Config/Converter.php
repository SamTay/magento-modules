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
    const REMOVAL_KEY = 'removal';
    const OPERATION_TYPE_KEY = 'type';

    /**
     * attribute_map attribute array [key => default_value]
     * @var array
     */
    protected $attributeMapAttributes = [
        self::ATTRIBUTE_MAP_CODE => null,
        self::ATTRIBUTE_MAP_MAPPER => null,
        self::SORT_KEY => 0,
        self::REMOVAL_KEY => true
    ];

    /**
     * key_map attribute array [key => default_value]
     * @var array
     */
    protected $keyMapAttributes = [
        self::KEY_MAP_FROM => null,
        self::KEY_MAP_TO => null,
        self::SORT_KEY => 0,
        self::REMOVAL_KEY => true
    ];

    /**
     * aggregate attribute array [key => default_value]
     * @var array
     */
    protected $aggregateAttributes = [
        self::AGGREGATE_ID => null,
        self::SORT_KEY => 0,
        self::REMOVAL_KEY => true
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
            $nodeType = $childNode->nodeName;
            $nodeData = [self::OPERATION_TYPE_KEY => $nodeType];
            switch($nodeType) {
                case (self::ENTITY_ATTRIBUTE_MAP):
                    $this->_addAttributes($nodeData, $this->attributeMapAttributes, $childNode);
                    $data[self::ENTITY_ATTRIBUTE_MAP][$operationId++] = $nodeData;
                    break;
                case (self::ENTITY_KEY_MAP):
                    $this->_addAttributes($nodeData, $this->keyMapAttributes, $childNode);
                    $data[self::ENTITY_KEY_MAP][$operationId++] = $nodeData;
                    break;
                case(self::ENTITY_KEY_AGGREGATE):
                    $this->_addAttributes($nodeData, $this->aggregateAttributes, $childNode);
                    $nodeData[self::AGGREGATE_COLLAPSE_KEY] = [];
                    foreach($childNode->childNodes as $keyNode) {
                        if ($keyNode->nodeName == 'key') {
                            $nodeData[self::AGGREGATE_COLLAPSE_KEY][] = $keyNode->attributes->getNamedItem('id')->nodeValue;
                        }
                    }
                    $data[self::ENTITY_KEY_AGGREGATE][$operationId++] = $nodeData;
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
        $operations = array_merge(
            array_values($data[self::ENTITY_KEY_MAP]),
            array_values($data[self::ENTITY_KEY_AGGREGATE]),
            array_values($data[self::ENTITY_ATTRIBUTE_MAP])
        );
        usort($operations, function($opA, $opB) {
            // Favor key_map -> aggregate -> attribute_map
            if ($opA[self::SORT_KEY] == $opB[self::SORT_KEY]) {
                $typeA = $opA[self::OPERATION_TYPE_KEY];
                $typeB = $opB[self::OPERATION_TYPE_KEY];
                if ($typeA == $typeB) {
                    return 0;
                }
                // One of these search results !== false -- if either returns 1, favor $typeA
                // Succint :)
                $indexKeyMap = array_search(self::ENTITY_KEY_MAP, [$typeB, $typeA]);
                $indexAttrMap = array_search(self::ENTITY_ATTRIBUTE_MAP, [$typeA, $typeB]);
                return in_array(1, [$indexAttrMap, $indexKeyMap], true) ? -1 : 1;
            }
            return $opA[self::SORT_KEY] < $opB[self::SORT_KEY] ? -1 : 1;
        });
        return $operations;
    }

    /**
     * Take DOM node and $map array, add attributes to $nodeData
     *
     * @param $nodeData
     * @param $map
     * @param $node
     * @return array
     */
    private function _addAttributes(&$nodeData, $map, $node)
    {
        foreach($map as $key => $default) {
            $attribute = $node->attributes->getNamedItem($key);
            $nodeData[$key] = is_null($attribute) ? $default : $attribute->nodeValue;
            // If key should be boolean, cast to boolean
            if (in_array($key, [self::REMOVAL_KEY])) {
                $nodeData[$key] = filter_var($nodeData[$key], FILTER_VALIDATE_BOOLEAN);
            }
        }
    }
}
