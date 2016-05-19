<?php
/**
 * @package     BlueAcorn\EntityMap
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\EntityMap\Import\Config;

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
        $cacheId = 'ba_entity_map_import_config_cache'
    ) {
        parent::__construct($reader, $cache, $cacheId);
    }

    /**
     * Get all entity info
     *
     * @param $entityType
     * @return array
     */
    public function getEntityInfo($entityType)
    {
        return $this->get($entityType);
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
     * Get default mapper if exists
     *
     * @param $entityType
     * @return string|null
     */
    public function getDefaultMapper($entityType)
    {
        return $this->getByArray([$entityType, Converter::ENTITY_DEFAULT_MAPPER]);
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
