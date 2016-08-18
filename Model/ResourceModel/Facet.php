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
use Magento\Framework\Db\Select;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use BlueAcorn\LayeredNavigation\Model\Facet as FacetModel;

class Facet
{
    const TABLE_CATALOG_PRODUCT_INDEX_EAV = 'catalog_product_index_eav';
    const TABLE_CATALOG_PRODUCT_INDEX_EAV_DECIMAL = 'catalog_product_index_eav_decimal';

    /** @var StoreManagerInterface */
    protected $storeManager;

    /** @var ResourceConnection */
    protected $resource;

    /** @var Facet\Price */
    protected $priceResource;

    /**
     * Facet constructor.
     * @param ResourceConnection $resource
     * @param StoreManagerInterface $storeManager
     * @param Facet\Price $priceResource
     */
    public function __construct(
        ResourceConnection $resource,
        StoreManagerInterface $storeManager,
        Facet\Price $priceResource
    ) {
        $this->storeManager = $storeManager;
        $this->resource = $resource;
        $this->priceResource = $priceResource;
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
            ? $connection->quoteInto("{$tableAlias}.value IN (?)", $attributeValue)
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
     * Add decimal filter to collection
     *
     * @param ProductCollection $collection
     * @param Attribute $attribute
     * @param $from
     * @param $to
     */
    public function addDecimalFilter(ProductCollection $collection, Attribute $attribute, $from, $to)
    {
        $connection = $this->getConnection();
        $tableAlias = sprintf('%s_idx', $attribute->getAttributeCode());
        $conditions = [
            "{$tableAlias}.entity_id = e.entity_id",
            $connection->quoteInto("{$tableAlias}.attribute_id = ?", $attribute->getAttributeId()),
            $connection->quoteInto("{$tableAlias}.store_id = ?", $collection->getStoreId()),
        ];

        $collection->getSelect()->join(
            [$tableAlias => $this->getMainTable()],
            implode(' AND ', $conditions),
            []
        );

        $collection->getSelect()->where(
            "{$tableAlias}.value >= ?",
            $from
        )->where(
            "{$tableAlias}.value < ?",
            $to
        );
    }

    /**
     * Add price filter to collection
     *
     * @param ProductCollection $collection
     * @param $from
     * @param $to
     */
    public function addPriceFilter(ProductCollection $collection, $from, $to)
    {
        $this->priceResource->addFilter($collection, $from, $to);
    }

    /**
     * Get faceted data for facet model
     *
     * @param FacetModel $facet
     * @return array
     */
    public function getFacetedData(FacetModel $facet)
    {
        $select = clone $facet->getCollection()->getSelect();
        $this->resetSelect($select);

        $connection = $this->getConnection();
        $attribute = $facet->getAttribute();
        $tableAlias = sprintf('%s_idx', $attribute->getAttributeCode());
        $attributeValue = $facet->getAttributeValue();
        // Interpret attributeValue
        if (is_string($attributeValue) && strpos($attributeValue, ',') !== false) {
            $attributeValue = explode(',', $attributeValue);
        }
        $multiValueCond = is_array($attributeValue)
            ? $connection->quoteInto("value = {$tableAlias}.value OR value IN (?)", $attributeValue)
            : $connection->quoteInto("value = {$tableAlias}.value OR value = ?", $attributeValue);
        $innerSelect = $this->getConnection()->select()
            ->from(self::TABLE_CATALOG_PRODUCT_INDEX_EAV, new \Zend_Db_Expr('COUNT(DISTINCT(entity_id))'))
            ->where('attribute_id = ?', $attribute->getAttributeId()) // todo test with this gone
            ->where($multiValueCond)
            ->__toString();
        $conditions = [
            "{$tableAlias}.entity_id = e.entity_id",
            $connection->quoteInto("{$tableAlias}.attribute_id = ?", $attribute->getAttributeId()),
            $connection->quoteInto("{$tableAlias}.store_id = ?", $this->storeManager->getStore()->getId()),
        ];

        $select->join(
            [$tableAlias => self::TABLE_CATALOG_PRODUCT_INDEX_EAV],
            join(' AND ', $conditions),
            ['value', "($innerSelect) as count"]
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

    /**
     * Reset columns, order, limitation
     *
     * @param Select $select
     */
    protected function resetSelect(Select $select)
    {
        $select->reset(Select::COLUMNS);
        $select->reset(Select::ORDER);
        $select->reset(Select::LIMIT_COUNT);
        $select->reset(Select::LIMIT_OFFSET);
    }
}
