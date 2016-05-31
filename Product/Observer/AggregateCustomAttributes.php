<?php
/*
 * @package     BlueAcorn\AmqpProduct
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\AmqpProduct\Observer;

use Magento\Catalog\Model\Product as ProductModel;
use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Class AggregateCustomAttributes
 * Listens for catalog_product_entitymap_decode_after to aggregate custom attributes
 * (Without this, webapi service input processor throws exceptions)
 */
class AggregateCustomAttributes implements ObserverInterface
{
    const CUSTOM_ATTRIBUTES_KEY = 'custom_attributes';

    /**
     * @var ProductModel
     */
    private $productModel;

    /**
     * AggregateCustomAttributes constructor.
     * @param ProductModel $productModel
     */
    public function __construct(
        ProductModel $productModel
    ) {
        $this->productModel = $productModel;
    }

    /**
     * Moves all custom attributes under a 'custom_attributes' key
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        /** @var DataObject $dataObject */
        $dataObject = $observer->getDataObject();
        $customAttributes = array_filter($this->getProductCustomAttributeCodes(), [$dataObject, 'hasData']);
        if ($customAttributes) {
            $customAttributeData = $dataObject->toArray($customAttributes);
            $preExistingData = $dataObject->getData(self::CUSTOM_ATTRIBUTES_KEY) ?: [];
            // Preference goes to attributes already existing under 'custom_attributes' key
            $dataObject->setData(self::CUSTOM_ATTRIBUTES_KEY, array_merge($customAttributeData, $preExistingData));
            $dataObject->unsetData($customAttributes);
        }
    }

    /**
     * Get product custom attribute codes
     *
     * @return mixed
     */
    public function getProductCustomAttributeCodes()
    {
        $accessor = $this->getCustomAttributesAccessor();
        return $accessor();
    }

    /**
     * Get callable that returns product custom attributes
     *
     * Object oriented hack because product model doesn't implement getters for interfaceAttributes property.
     * Could also do this through reflection on that property & \Magento\Catalog\Model\Product\Attribute\Repository
     * but why go through the effort when they already put this logic into getCustomAttributesCodes
     *
     * @return \Closure
     */
    private function getCustomAttributesAccessor()
    {
        $accessor = function() {
            return $this->getCustomAttributesCodes();
        };
        return $accessor->bindTo($this->productModel, $this->productModel); //todo test this return is ok
    }
}
