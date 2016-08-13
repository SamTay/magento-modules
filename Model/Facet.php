<?php
/**
 * @package     BlueAcorn\LayeredNavigation
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\LayeredNavigation\Model;

use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use BlueAcorn\LayeredNavigation\Model\ResourceModel\Facet as FacetResource;

class Facet
{
    /** @var  ProductCollection */
    protected $collection;

    /** @var Attribute */
    protected $attribute;

    /** @var int|string */
    protected $attributeValue;

    /** @var FacetResource */
    protected $resource;

    /**
     * Facet constructor.
     * @param ProductCollection $collection
     * @param Attribute $attribute
     * @param int|string $attributeValue
     * @param FacetResource $resource
     */
    public function __construct(
        ProductCollection $collection,
        Attribute $attribute,
        $attributeValue,
        FacetResource $resource
    ) {
        $this->collection = $collection;
        $this->attribute = $attribute;
        $this->attributeValue = $attributeValue;
        $this->resource = $resource;
    }

    /**
     * Get result count per attribute value
     *
     * @return array
     */
    public function getFacetedData()
    {
        return $this->resource->getFacetedData($this);
    }

    /**
     * Get collection
     *
     * @return ProductCollection
     */
    public function getCollection()
    {
        return $this->collection;
    }

    /**
     * Get attribute
     *
     * @return Attribute
     */
    public function getAttribute()
    {
        return $this->attribute;
    }

    /**
     * Get applied attribute value
     *
     * @return int|string
     */
    public function getAttributeValue()
    {
        return $this->attributeValue;
    }

    /**
     * Get attribute code
     *
     * @return string
     */
    public function getAttributeCode()
    {
        return $this->attribute->getAttributeCode();
    }

    /**
     * Get connection
     *
     * @return \Magento\Framework\DB\Adapter\AdapterInterface
     */
    public function getConnection()
    {
        return $this->resource->getConnection();
    }
}
