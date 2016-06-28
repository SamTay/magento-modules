<?php
/**
 * @package     BlueAcorn\EntityMap
 * @version     0.2.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\EntityMap;

use BlueAcorn\EntityMap\Decode\Config\Converter as DecodeConfigConverter;
use BlueAcorn\EntityMap\Decode\Config\Data as DecodeConfig;
use Magento\Framework\DataObject;
use Magento\Framework\Event\Manager as EventManager;

class Decoder implements ConverterInterface
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
     * @var EventManager
     */
    protected $eventManager;

    /**
     * Map operation type => operation method
     * @var array
     */
    protected $operationMethodMap = [
        DecodeConfigConverter::ENTITY_KEY_MAP => '_mapKey',
        DecodeConfigConverter::ENTITY_KEY_AGGREGATE => '_aggregateKeys',
        DecodeConfigConverter::ENTITY_ATTRIBUTE_MAP => '_mapAttribute',
    ];

    /**
     * Decoder constructor.
     * @param DecodeConfig $decodeConfig
     * @param MapperFactory $mapperFactory
     * @param EventManager $eventManager
     */
    public function __construct(
        DecodeConfig $decodeConfig,
        MapperFactory $mapperFactory,
        EventManager $eventManager
    ) {
        $this->decodeConfig = $decodeConfig;
        $this->mapperFactory = $mapperFactory;
        $this->eventManager = $eventManager;
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
    public function convert(array $data, $entityType)
    {
        try {
            $this->initConfig($entityType);
            $dataObject = new DataObject($data);
            $this->eventManager->dispatch('entity_entitymap_decode_before', ['data_object' => $dataObject]);
            $this->eventManager->dispatch($entityType . '_entitymap_decode_before', ['data_object' => $dataObject]);
            $this->_decode($dataObject);
            $this->eventManager->dispatch('entity_entitymap_decode_after', ['data_object' => $dataObject]);
            $this->eventManager->dispatch($entityType . '_entitymap_decode_after', ['data_object' => $dataObject]);
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
     *
     * @param DataObject $data
     */
    private function _decode(DataObject $data)
    {
        foreach($this->config as $operation) {
            $method = $this->operationMethodMap[$operation[DecodeConfigConverter::OPERATION_TYPE_KEY]];
            call_user_func([$this, $method], $data, $operation);
        }
    }

    /**
     * Map key (from <key_map> nodes)
     *
     * @param DataObject $dataObject
     * @param $operation
     */
    private function _mapKey(DataObject $dataObject, $operation)
    {
        if ($dataObject->hasData($operation[DecodeConfigConverter::KEY_MAP_FROM])) {
            $dataObject->setData(
                $operation[DecodeConfigConverter::KEY_MAP_TO],
                $dataObject->getData($operation[DecodeConfigConverter::KEY_MAP_FROM])
            );
            $dataObject->unsetData($operation[DecodeConfigConverter::KEY_MAP_FROM]);
        }
    }

    /**
     * Collapse multiple keys to single aggregate key (from <aggregate> nodes)
     *
     * @param DataObject $dataObject
     * @param $operation
     */
    private function _aggregateKeys(DataObject $dataObject, $operation)
    {
        $aggregateId = $operation[DecodeConfigConverter::AGGREGATE_ID];
        $keysToAggregate = array_filter(
            $operation[DecodeConfigConverter::AGGREGATE_COLLAPSE_KEY],
            [$dataObject, 'hasData']
        );
        if (!$keysToAggregate) {
            return;
        }
        $aggregatedData = $dataObject->toArray($keysToAggregate);
        $dataObject->setData($aggregateId, $aggregatedData);
        $dataObject->unsetData($keysToAggregate);
    }

    /**
     * Map attribute
     *
     * @param DataObject $dataObject
     * @param $operation
     */
    private function _mapAttribute(DataObject $dataObject, $operation)
    {
        $key = $operation[DecodeConfigConverter::ATTRIBUTE_MAP_CODE];
        $mapperClass = $operation[DecodeConfigConverter::ATTRIBUTE_MAP_MAPPER];
        if ($dataObject->hasData($key)) {
            $mapper = $this->mapperFactory->get($mapperClass);
            $mappedData = $mapper->map($key, $dataObject->getData($key));
            $dataObject->unsetData($key); // Unset data first, otherwise mappers returning the same key are thrown away
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
        $this->config = $this->decodeConfig->getSortedOperations($entityType);
    }

    /**
     * Reset entity specific configuration
     */
    private function resetConfig()
    {
        $this->config = [];
    }
}
