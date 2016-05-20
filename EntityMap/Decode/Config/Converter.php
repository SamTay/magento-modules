<?php
/**
 * @package     BlueAcorn\EntityMap
 * @version     1.0.0
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
    const ENTITY_KEY_MAP = 'key_map';
    const ENTITY_KEY_AGGREGATE = 'key_aggregate';
    const ENTITY_KEY_COLLAPSE = 'key_collapse';
    const ENTITY_ATTRIBUTE_MAP = 'attribute_map';
    const ENTITY_DEFAULT_MAPPER = 'default_mapper';

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
            self::ENTITY_DEFAULT_MAPPER => '',
            self::ENTITY_ATTRIBUTE_MAPS => [],
            self::ENTITY_KEY_MAP => [],
            self::ENTITY_KEY_AGGREGATE => [],
            self::ENTITY_KEY_COLLAPSE => []
        ];
        /** @var $childNode \DOMNode */
        foreach($entityNode->childNodes as $childNode) {
            switch($childNode->nodeName) {
                case ('default_mapper'):
                    $data[self::ENTITY_DEFAULT_MAPPER] = $childNode->attributes->getNamedItem('class');
                    break;
                case ('attribute_map'):
                    $attributeCode = $childNode->attributes->getNamedItem('code');
                    $mapperClass = $childNode->attributes->getNamedItem('mapper');
                    $data[self::ENTITY_ATTRIBUTE_MAPS][$attributeCode] = $mapperClass;
                    break;
                case ('key'):
                    $from = $childNode->attributes->getNamedItem('from');
                    $to = $childNode->attributes->getNamedItem('to');
                    $data[self::ENTITY_KEY_MAP][$from] = $to;
                    break;
                case('aggregate'):
                    $aggregateId = $childNode->attributes->getNamedItem('id');
                    $keysToAggregate = [];
                    foreach($childNode->childNodes as $keyNode) {
                        $keysToAggregate[] = $keyNode->attributes->getNamedItem('id');
                    }
                    $data[self::ENTITY_KEY_AGGREGATE][$aggregateId] = $keysToAggregate;
                    $collapse = array_fill_keys($keysToAggregate, $aggregateId);
                    $data[self::ENTITY_KEY_COLLAPSE] = array_merge($data[self::ENTITY_KEY_COLLAPSE], $collapse);
                    break;
            }
        }

        return $data;
    }
}
