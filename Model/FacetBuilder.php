<?php
/**
 * @package     BlueAcorn\LayeredNavigation
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\LayeredNavigation\Model;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Framework\ObjectManagerInterface;

class FacetBuilder
{
    const INSTANCE_NAME = 'BlueAcorn\LayeredNavigation\Model\Facet';
    const ATTRIBUTE_CODE_KEY = 'attributeCode';
    const COLLECTION_KEY = 'collection';

    /** @var ObjectManagerInterface */
    protected $objectManager;

    /** @var array */
    protected $data;

    /**
     * FacetBuilder constructor.
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Set collection
     *
     * @param ProductCollection $collection
     * @return $this
     */
    public function setCollection(ProductCollection $collection)
    {
        $this->data[self::COLLECTION_KEY] = $collection;
        return $this;
    }

    /**
     * Set attribute code
     *
     * @param $attributeCode
     * @return $this
     */
    public function setAttributeCode($attributeCode)
    {
        $this->data[self::ATTRIBUTE_CODE_KEY] = $attributeCode;
        return $this;
    }

    /**
     * Create facet
     *
     * @return Facet
     */
    public function create()
    {
        $instance = $this->objectManager->create(self::INSTANCE_NAME, $this->data);
        $this->clear();
        return $instance;
    }

    /**
     * Clear facet properties
     *
     * @return $this
     */
    public function clear()
    {
        $this->data = [];
        return $this;
    }
}
