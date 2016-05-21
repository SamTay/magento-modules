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
use Magento\Framework\DataObject;

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
            $dataObject = new DataObject($data);
            $this->_decode($dataObject);
            return $dataObject->getData();
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
     * @param DataObject $data
     */
    private function _decode(DataObject $data)
    {
        // First map all keys one-to-one
        $this->_mapKeys($data);

        // Then collapse/aggregate all specified keys
        $this->_collapseKeys($data);

        // Then map [key => value] pairs
        $this->_mapAttributes($data);
    }

    /**
     * Map all keys (from <key_map> nodes)
     *
     * @param DataObject $dataObject
     */
    private function _mapKeys(DataObject $dataObject)
    {
        $keysToMap = array_keys($this->config[Converter::ENTITY_KEY_MAP]);
        $dataToMap = $dataObject->toArray($keysToMap);
        $mappedData = array_combine($this->config[Converter::ENTITY_KEY_MAP], $dataToMap) ?: [];
        $dataObject->addData($mappedData);
        $dataObject->unsetData($keysToMap);
    }

    /**
     * Collapse multi keys to single aggregate keys (from <aggregate> nodes)
     *
     * @param DataObject $dataObject
     */
    private function _collapseKeys(DataObject $dataObject)
    {
        $aggregationMap = $this->config[Converter::ENTITY_KEY_AGGREGATE];
        foreach($aggregationMap as $aggregateId => $keysToAggregate) {
            $aggregatedData = $dataObject->toArray($keysToAggregate);
            $dataObject->setData($aggregateId, $aggregatedData);
        }
        // Unset data AFTER all aggregation complete, in case we are aggregating the same keys to different places
        $dataObject->unsetData($this->config[Converter::ENTITY_KEY_COLLAPSE]);
    }

    /**
     * Map attributes
     * TODO: SHIT! We don't want toArray to return empty values when keys dont exist
     * TODO: For example, if update just has price update, this will remove all other attribute values. Fix!!!
     *
     * @param DataObject $dataObject
     */
    private function _mapAttributes(DataObject $dataObject)
    {
        $keysToMap = array_keys($this->config[Converter::ENTITY_ATTRIBUTE_MAP]);
        $dataToMap = $dataObject->toArray($keysToMap);
        $dataObject->unsetData($keysToMap); // Unset data completely -- keys should be returned by mapper if necessary
        foreach($dataToMap as $key => $value) {
            $mapperClass = $this->config[Converter::ENTITY_ATTRIBUTE_MAP][$key];
            $entityType = $this->config[Converter::ENTITY_TYPE];
            $mapper = $this->mapperFactory->get($mapperClass, $entityType);
            $mappedData = $mapper->map([$key => $value]);
            $dataObject->addData($mappedData);
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
