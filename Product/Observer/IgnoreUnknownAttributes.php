<?php
/*
 * @package     BlueAcorn\AmqpProduct
 * @version     0.2.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\AmqpProduct\Observer;

use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Framework\Api\MetadataObjectInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Reflection\FieldNamer;
use Magento\Framework\Reflection\MethodsMap;

/**
 * Class IgnoreUnknownAttributes
 * Listens for catalog_product_entitymap_decode_after to remove unknown attributes
 * that would otherwise throw exceptions
 */
class IgnoreUnknownAttributes implements ObserverInterface
{
    const XML_PATH_IGNORE_UNKNOWN = 'ba_amqp/product/ignore_unknown';
    const PRODUCT_INTERFACE = '\Magento\Catalog\Api\Data\ProductInterface';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var ProductAttributeRepositoryInterface
     */
    protected $productAttributeRepository;

    /**
     * @var array
     */
    protected $whitelist = ['custom_attributes'];

    /**
     * @var MethodsMap
     */
    protected $methodsMap;

    /**
     * @var array
     */
    protected $interfaceAttributes = [];

    /**
     * @var FieldNamer
     */
    protected $fieldNamer;

    /**
     * IgnoreUnknownAttributes constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param ProductAttributeRepositoryInterface $productAttributeRepository
     * @param MethodsMap $methodsMap
     * @param FieldNamer $fieldNamer
     * @param array $whitelist
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ProductAttributeRepositoryInterface $productAttributeRepository,
        MethodsMap $methodsMap,
        FieldNamer $fieldNamer,
        array $whitelist = []
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->productAttributeRepository = $productAttributeRepository;
        $this->methodsMap = $methodsMap;
        $this->fieldNamer = $fieldNamer;
        $this->whitelist = array_merge($this->whitelist, $whitelist);
    }

    /**
     * Remove unknown attributes unless ignore flag is set to false
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        if (!$this->scopeConfig->isSetFlag(self::XML_PATH_IGNORE_UNKNOWN)) {
            return;
        }
        /** @var DataObject $dataObject */
        $dataObject = $observer->getDataObject();
        $dataObjectKeys = array_keys($dataObject->getData());
        $knownAttributeCodes = array_map(function($metaData) {
            /** Actually current repository implementation does not return correct interface, but method is still defined */
            /** @var MetadataObjectInterface $metaData */
            return $metaData->getAttributeCode();
        }, $this->productAttributeRepository->getCustomAttributesMetadata());

        $unknownKeys = array_diff($dataObjectKeys, $knownAttributeCodes, $this->getInterfaceAttributes(), $this->whitelist);
        if ($unknownKeys) {
            $dataObject->unsetData($unknownKeys);
        }
    }

    /**
     * Get interface attributes of product model
     *
     * @return array
     */
    protected function getInterfaceAttributes()
    {
        if (!$this->interfaceAttributes) {
            $methods = array_keys($this->methodsMap->getMethodsMap(self::PRODUCT_INTERFACE));
            $this->interfaceAttributes = array_map(function($methodName) {
                return $this->fieldNamer->getFieldNameForMethodName($methodName);
            }, $methods);
        }
        return $this->interfaceAttributes;
    }
}
