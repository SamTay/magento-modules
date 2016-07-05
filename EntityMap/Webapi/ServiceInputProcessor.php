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
use Magento\Framework\Reflection\TypeProcessor as FrameworkTypeProcessor;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Reflection\MethodsMap;
use Magento\Framework\Webapi\CustomAttributeTypeLocatorInterface;

/**
 * Class ServiceInputProcessor
 * TODO Cange to module and create shared virtualType
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
    protected $schemaMap = [];

    /**
     * ServiceInputProcessor constructor.
     * @param FrameworkTypeProcessor $typeProcessor
     * @param ObjectManagerInterface $objectManager
     * @param AttributeValueFactory $attributeValueFactory
     * @param CustomAttributeTypeLocatorInterface $customAttributeTypeLocator
     * @param MethodsMap $methodsMap
     * @param Decoder $decoder
     * @param DecodeConfig $decodeConfig
     * @param array $schemaMap
     */
    public function __construct(
        FrameworkTypeProcessor $typeProcessor,
        ObjectManagerInterface $objectManager,
        AttributeValueFactory $attributeValueFactory,
        CustomAttributeTypeLocatorInterface $customAttributeTypeLocator,
        MethodsMap $methodsMap,
        Decoder $decoder,
        DecodeConfig $decodeConfig,
        array $schemaMap = []
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
        $this->initSchema($schemaMap);
    }

    /**
     * {@inheritdoc}
     */
    protected function _createFromArray($className, $data)
    {
        $schema = ltrim($className, '\\');
        if (array_key_exists($schema, $this->schemaMap)) {
            $data = $this->decoder->convert($data, $this->schemaMap[$schema]);
        }
        return parent::_createFromArray($className, $data);
    }

    /**
     * Initialize schema map with additions/overrides possible from di.xml
     *
     * @param array $injectedMap
     */
    protected function initSchema(array $injectedMap = [])
    {
        $config = [];
        foreach($this->decodeConfig->get() as $entityType => $entityData) {
            $schema = ltrim($entityData[DecodeConfigConverter::ENTITY_SCHEMA], '\\');
            $config[$schema] = $entityType;
        }
        $this->schemaMap = array_merge($config, $injectedMap);
    }
}
