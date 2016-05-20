<?php
/**
 * @package     BlueAcorn\EntityMap
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\EntityMap;

use BlueAcorn\EntityMap\Decode\Config\Converter;
use BlueAcorn\EntityMap\Decode\Config\Data as DecodeConfig;

class Decoder
{
    /**
     * @var DecodeConfig
     */
    protected $decodeConfig;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var MapperFactory
     */
    protected $mapperFactory;

    /**
     * Converter constructor.
     * @param DecodeConfig $decodeConfig
     * @param MapperFactory $mapperFactory
     */
    public function __construct(
        DecodeConfig $decodeConfig,
        MapperFactory $mapperFactory
    ) {
        $this->decodeConfig = $decodeConfig;
        $this->mapperFactory = $mapperFactory;
    }

    /**
     * TODO: What about nested entity decoding? init/reset config might break things
     * Decode entity
     *
     * @param array $data
     * @param $entityType
     * @return array
     * @throws \Exception
     */
    public function decode(array $data, $entityType)
    {
        try {
            $this->initConfig($entityType);
            return $this->_decode($data);
        } catch (\Exception $e) {
            throw new \Exception('Error occurred during decoding', 0, $e);
        } finally {
            $this->resetConfig();
        }
    }

    /**
     * Decode data based on current set entity type.
     * Note this implementation is NOT recursive, as that would remove flexibility in situations
     * where keys can match in outer and inner arrays. Instead, to modify keys in a nested array,
     * use an <attribute_map> node.
     * TODO: To allow a nested decoding process, perhaps allow '/' in the keys
     *
     * @param $data
     */
    private function _decode($data)
    {
        // First map all keys one-to-one
        $this->_mapKeys($data);

        // Then collapse/aggregate all specified keys
        $this->_collapseKeys($data);

        // Then map [key => value] pairs
        $this->_mapAttributes($data);

        return $data;
    }

    /**
     * Map all keys (from <key_map> nodes)
     *
     * @param $data
     */
    private function _mapKeys(&$data)
    {
        $keysToUnset = [];
        foreach($data as $origKey => &$origValue) {
            if (array_key_exists($origKey, $this->config[Converter::ENTITY_KEY_MAP])) {
                $newKey = $this->config[Converter::ENTITY_KEY_MAP][$origKey];
                $data[$newKey] = $origValue;
                $keysToUnset[] = $origKey;
            }
        }
        $this->_unsetKeys($data, $keysToUnset);
    }

    /**
     * Collapse multi keys to single aggregate keys (from <aggregate> nodes)
     *
     * @param $data
     */
    private function _collapseKeys(&$data)
    {
        $keysToUnset = [];
        foreach($data as $key => $value) {
            if (array_key_exists($key, $this->config[Converter::ENTITY_KEY_COLLAPSE])) {
                $aggregateId = $this->config[Converter::ENTITY_KEY_COLLAPSE];
                if (isset($data[$aggregateId])) {
                    if (is_array($data[$aggregateId])) {
                        $data[$aggregateId][$key] = $value;
                    } else {
                        $data[$aggregateId] = [$aggregateId => $data[$aggregateId], $key => $value];
                    }
                } else {
                    $data[$aggregateId] = [$key => $value];
                }
                $keysToUnset[] = $key;
            }
        }
        $this->_unsetKeys($data, $keysToUnset);
    }

    /**
     * Map key,value pairs
     *
     * @param $data
     */
    private function _mapAttributes(&$data)
    {
        $keysToUnset = [];
        foreach($data as $origKey => &$origValue) {
            if (array_key_exists($origKey, $this->config[Converter::ENTITY_ATTRIBUTE_MAP])) {
                list($newKey, $newValue) = $this->mapperFactory->get($this->config[Converter::ENTITY_TYPE])
                    ->setAttributeCode($origKey)
                    ->map($origKey, $origValue);
                if ($newKey === $origKey) {
                    $origValue = $newValue;
                } else {
                    $data[$newKey] = $newValue;
                    $keysToUnset[] = $origKey;
                }
            }
        }
        $this->_unsetKeys($data, $keysToUnset);
    }

    /**
     * Unset keys by reference
     *
     * @param $data
     * @param $keysToUnset
     */
    private function _unsetKeys(&$data, $keysToUnset)
    {
        foreach($keysToUnset as $key) {
            unset($data[$key]);
        }
    }

    /**
     * Set configuration to entity type
     *
     * @param $entityType
     */
    private function initConfig($entityType)
    {
        $this->config = $this->decodeConfig->getEntityInfo($entityType);
    }

    /**
     * Reset entity specific configuration
     */
    private function resetConfig()
    {
        $this->config = [];
    }
}
