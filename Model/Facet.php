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
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Store\Model\StoreManagerInterface;

class Facet
{
    /** @var  ProductCollection */
    protected $collection;

    /** @var Attribute */
    protected $attribute;

    /** @var StoreManagerInterface */
    protected $storeManager;

    /** @var ResourceConnection */
    protected $resource;

    /**
     * Facet constructor.
     * @param ProductCollection $collection
     * @param Attribute $attribute
     * @param ResourceConnection $resource
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ProductCollection $collection,
        Attribute $attribute,
        ResourceConnection $resource,
        StoreManagerInterface $storeManager
    ) {
        $this->collection = $collection;
        $this->attribute = $attribute;
        $this->storeManager = $storeManager;
        $this->resource = $resource;
    }

    /**
     * Get result count per attribute value
     *
     * @return array
     */
    public function getFacetedData()
    {
        // clone select from collection
        $select = clone $this->collection->getSelect();
        // reset columns, order and limitation conditions
        $select->reset(Select::COLUMNS);
        $select->reset(Select::ORDER);
        $select->reset(Select::LIMIT_COUNT);
        $select->reset(Select::LIMIT_OFFSET);

        $connection = $this->getConnection();
        $attribute = $this->getAttribute();
        $tableAlias = sprintf('%s_idx', $attribute->getAttributeCode());
        $conditions = [
            "{$tableAlias}.entity_id = e.entity_id",
            $connection->quoteInto("{$tableAlias}.attribute_id = ?", $attribute->getAttributeId()),
            $connection->quoteInto("{$tableAlias}.store_id = ?", $this->storeManager->getStore()->getId()),
        ];

        $select->join(
            [$tableAlias => 'catalog_product_index_eav'],
            join(' AND ', $conditions),
            ['value', 'count' => new \Zend_Db_Expr("COUNT({$tableAlias}.entity_id)")]
        )->group(
            "{$tableAlias}.value"
        );

        return $connection->fetchPairs($select);
    }

    /**
     * Check if facet should skip this collection modifier
     *
     * @param $method
     * @param $args
     * @return bool
     */
    public function shouldSkip($method, $args)
    {
        return $method == 'addFieldToFilter'
            && $args && $args[0] == $this->getAttributeCode();
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
     * @return AdapterInterface
     */
    public function getConnection()
    {
        return $this->resource->getConnection();
    }
}
