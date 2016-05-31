<?php
/**
 * @package     BlueAcorn\EntityMap
 * @version     0.2.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\EntityMap\Decode\Config;

use Magento\Framework\Config\CacheInterface;

/**
 * Class for access to EntityMap configuration.
 */
class Data extends \Magento\Framework\Config\Data
{
    /**
     * Initialize dependencies.
     *
     * @param Reader $reader
     * @param CacheInterface $cache
     * @param string $cacheId
     */
    public function __construct(
        Reader $reader,
        CacheInterface $cache,
        $cacheId = 'ba_entity_decode_config_cache'
    ) {
        parent::__construct($reader, $cache, $cacheId);
    }

    /**
     * Get all entity info, include empty arrays where necessary
     *
     * @param $entityType
     * @return array
     */
    public function getEntityInfo($entityType)
    {
        return [
            Converter::ENTITY_SCHEMA => $this->getEntitySchema($entityType),
            Converter::ENTITY_KEY_MAP => $this->getKeyMap($entityType),
            Converter::ENTITY_KEY_COLLAPSE => $this->getKeysToCollapse($entityType),
            Converter::ENTITY_ATTRIBUTE_MAP => $this->getAttributeMap($entityType),
            Converter::ENTITY_KEY_AGGREGATE => $this->getAggregateKeys($entityType)
        ];
    }

    /**
     * Get entity schema
     *
     * @param $entityType
     * @return string
     */
    public function getEntitySchema($entityType)
    {
        return $this->getByArray([$entityType, Converter::ENTITY_SCHEMA], '');
    }

    /**
     * Get associative key mapping array ['from1' => 'dest1']
     *
     * @param $entityType
     * @return array
     */
    public function getKeyMap($entityType)
    {
        return $this->getByArray([$entityType, Converter::ENTITY_KEY_MAP], []);
    }

    /**
     * Get keys for collapsing [key1 => aggregate, key2 => aggregate]
     *
     * @param $entityType
     * @return array
     */
    public function getKeysToCollapse($entityType)
    {
        return $this->getByArray([$entityType, Converter::ENTITY_KEY_COLLAPSE], []);
    }

    /**
     * Get aggregates [aggregate => [key1, key2, key3]]
     *
     * @param $entityType
     * @return array
     */
    public function getAggregateKeys($entityType)
    {
        return $this->getByArray([$entityType, Converter::ENTITY_KEY_AGGREGATE], []);
    }

    /**
     * Get associative attribute mapping array ['attribtueCode' => 'mapperClass']
     *
     * @param $entityType
     * @return array
     */
    public function getAttributeMap($entityType)
    {
        return $this->getByArray([$entityType, Converter::ENTITY_ATTRIBUTE_MAP], []);
    }

    /**
     * Avoid excessive concatenation
     *
     * @param array $path
     * @param null $default
     * @return array|mixed|null
     */
    public function getByArray(array $path, $default = null)
    {
        $stringPath = implode('/', $path);
        return $this->get($stringPath, $default);
    }
}
