<?php
/**
 * @package     BlueAcorn\AmqpBase
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\AmqpBase\Observer;

use BlueAcorn\EntityMap\Decode\Config\Data as DecodeConfig;
use BlueAcorn\EntityMap\Decode\Config\Converter as DecodeConverter;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;
use BlueAcorn\EntityMap\Decoder;

/**
 * Class EntityDecode
 * Listens for amqp_message_convert_before to decode messages before the webapi object input parser
 */
class EntityDecode implements ObserverInterface
{
    /**
     * @var array
     */
    private $schemaMap;

    /**
     * @var Decoder
     */
    private $decoder;

    /**
     * @var DecodeConfig
     */
    private $decodeConfig;

    /**
     * EntityDecode constructor.
     *
     * @param Decoder $decoder
     * @param DecodeConfig $decodeConfig
     * @param array $schemaMap
     */
    public function __construct(
        Decoder $decoder,
        DecodeConfig $decodeConfig,
        array $schemaMap = []
    ) {
        $this->decoder = $decoder;
        $this->decodeConfig = $decodeConfig;
        $this->initializeSchemaMap($schemaMap);
    }

    /**
     * Decodes message using EntityMap library,
     * configured by schemaMap (set from di.xml)
     *
     * @param EventObserver $observer
     * @throws \Exception
     */
    public function execute(EventObserver $observer)
    {
        $eventSchema = $observer->getSchema();
        $message = $observer->getTransport()->getMessage();

        $isArray = false;
        if (substr($eventSchema, -2) == '[]') {
            $isArray = true;
            $eventSchema = substr($eventSchema, 0, -2);
        }
        foreach($this->schemaMap as $schema => $entityType) {
            if ($schema  == $eventSchema) {
                if (!$entityType) {
                    // Others may have injected $schema => false to avoid decoding
                    continue;
                }
                if ($isArray) {
                    $message = array_map(function($entity) use ($entityType) {
                        return $this->decoder->convert($entity, $entityType);
                    });
                } else {
                    $message = $this->decoder->convert($message, $entityType);
                }
                $observer->getTransport()->setMessage($message);
                return;
            }
        }
    }

    /**
     * Initialize schema map with additions/overrides possible from di.xml
     *
     * @param array $injectedMap
     */
    private function initializeSchemaMap(array $injectedMap = [])
    {
        if (!$this->schemaMap) {
            $config = [];
            foreach($this->decodeConfig->get() as $entityType => $entityInfo) {
                $config[$entityInfo[DecodeConverter::ENTITY_SCHEMA]] = $entityType;
            }
            $this->schemaMap = array_merge($config, $injectedMap);
        }
    }
}
