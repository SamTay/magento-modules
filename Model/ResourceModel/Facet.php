<?php
/**
 * @package     BlueAcorn\LayeredNavigation
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\LayeredNavigation\Model\ResourceModel;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use BlueAcorn\LayeredNavigation\Model\Facet as FacetModel;

class Facet
{
    const TABLE_CATALOG_PRODUCT_INDEX_EAV = 'catalog_product_index_eav';

    /** @var StoreManagerInterface */
    protected $storeManager;

    /** @var ResourceConnection */
    protected $resource;

    /**
     * Facet constructor.
     * @param ResourceConnection $resource
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ResourceConnection $resource,
        StoreManagerInterface $storeManager
    ) {
        $this->storeManager = $storeManager;
        $this->resource = $resource;
    }

    /**
     * Apply attribute filter to collection
     *
     * @param ProductCollection $collection
     * @param Attribute $attribute
     * @param $attributeValue
     */
    public function addAttributeFilter(ProductCollection $collection, Attribute $attribute, $attributeValue)
    {
        // Interpret attributeValue
        if (is_string($attributeValue) && strpos($attributeValue, ',') !== false) {
            $attributeValue = explode(',', $attributeValue);
        }
        $connection = $this->getConnection();
        $tableAlias = $attribute->getAttributeCode() . '_idx';
        $conditions = [
            "{$tableAlias}.entity_id = e.entity_id",
            $connection->quoteInto("{$tableAlias}.attribute_id = ?", $attribute->getAttributeId()),
            $connection->quoteInto("{$tableAlias}.store_id = ?", $collection->getStoreId()),
        ];
        $conditions[] = is_array($attributeValue)
            ? $connection->quoteInto("{$tableAlias}.value IN ?", $attributeValue)
            : $connection->quoteInto("{$tableAlias}.value = ?", $attributeValue);

        $collection->getSelect()->join(
            [$tableAlias => self::TABLE_CATALOG_PRODUCT_INDEX_EAV],
            implode(' AND ', $conditions),
            []
        );
    }

    /**
     * Add category filter to collection
     *
     * @param ProductCollection $collection
     * @param Category $category
     */
    public function addCategoryFilter(ProductCollection $collection, Category $category)
    {
        $collection->setStoreId($category->getStoreId())
            ->addCategoryFilter($category);
    }

    /**
     * Get faceted data for facet model
     *
     * @param FacetModel $facet
     * @return array
     */
    public function getFacetedData(FacetModel $facet)
    {
        // TODO include OR logic in count/group against $this->attributeValue
        $select = clone $facet->getCollection()->getSelect();
        // reset columns, order and limitation conditions
        $select->reset(Select::COLUMNS);
        $select->reset(Select::ORDER);
        $select->reset(Select::LIMIT_COUNT);
        $select->reset(Select::LIMIT_OFFSET);

        $connection = $this->getConnection();
        $attribute = $facet->getAttribute();
        $tableAlias = sprintf('%s_idx', $attribute->getAttributeCode());
        $conditions = [
            "{$tableAlias}.entity_id = e.entity_id",
            $connection->quoteInto("{$tableAlias}.attribute_id = ?", $attribute->getAttributeId()),
            $connection->quoteInto("{$tableAlias}.store_id = ?", $this->storeManager->getStore()->getId()),
        ];

        $select->join(
            [$tableAlias => self::TABLE_CATALOG_PRODUCT_INDEX_EAV],
            join(' AND ', $conditions),
            ['value', 'count' => new \Zend_Db_Expr("COUNT({$tableAlias}.entity_id)")]
        )->group(
            "{$tableAlias}.value"
        );

        return $connection->fetchPairs($select);
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
