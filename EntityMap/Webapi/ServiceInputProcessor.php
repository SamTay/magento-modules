<?php
/**
 * @package     BlueAcorn\EntityMap
 * @version     0.2.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\EntityMap\Webapi;

use BlueAcorn\EntityMap\Decoder;
use BlueAcorn\EntityMap\Decode\Config\Converter as DecodeConfigConverter;
use BlueAcorn\EntityMap\Decode\Config\Data as DecodeConfig;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Reflection\TypeProcessor;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Reflection\MethodsMap;
use Magento\Framework\Webapi\CustomAttributeTypeLocatorInterface;

/**
 * Class ServiceInputProcessor
 * Using inheritance instead of composition so that classes can easily inject
 * this class into __construct via di.xml instead of having to rewrite lots of code
 */
class ServiceInputProcessor extends \Magento\Framework\Webapi\ServiceInputProcessor
{
    /**
     * @var Decoder
     */
    protected $decoder;

    /**
     * @var DecodeConfig
     */
    protected $decodeConfig;

    /**
     * @var array
     */
    protected $schema = [];

    /**
     * ServiceInputProcessor constructor.
     * @param TypeProcessor $typeProcessor
     * @param ObjectManagerInterface $objectManager
     * @param AttributeValueFactory $attributeValueFactory
     * @param CustomAttributeTypeLocatorInterface $customAttributeTypeLocator
     * @param MethodsMap $methodsMap
     * @param Decoder $decoder
     * @param DecodeConfig $decodeConfig
     */
    public function __construct(
        TypeProcessor $typeProcessor,
        ObjectManagerInterface $objectManager,
        AttributeValueFactory $attributeValueFactory,
        CustomAttributeTypeLocatorInterface $customAttributeTypeLocator,
        MethodsMap $methodsMap,
        Decoder $decoder,
        DecodeConfig $decodeConfig
    ) {
        parent::__construct(
            $typeProcessor,
            $objectManager,
            $attributeValueFactory,
            $customAttributeTypeLocator,
            $methodsMap
        );
        $this->decoder = $decoder;
        $this->decodeConfig = $decodeConfig;
        $this->initSchema();
    }

    /**
     * {@inheritdoc}
     */
    public function convertValue($data, $type)
    {
        if (array_key_exists($type, $this->schema)) {
            $data = $this->decoder->convert($data, $this->schema[$type]);
        }
        return parent::convertValue($data, $type);
    }

    /**
     * Initialize schema map
     */
    protected function initSchema()
    {
        foreach($this->decodeConfig->get() as $entityType => $entityData) {
            $schemaType = $entityData[DecodeConfigConverter::ENTITY_SCHEMA];
            $this->schema[$schemaType] = $entityType;
        }
    }
}
