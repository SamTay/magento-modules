<?php
/**
 * @package     BlueAcorn\LayeredNavigation
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\LayeredNavigation\Model\Layer;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Catalog\Model\ResourceModel\Layer\Filter\Price as CatalogPriceResource;
use Magento\Framework\Db\Select;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;

class CollectionMirror
{
    const TABLE_CATALOG_PRODUCT_INDEX_EAV = 'catalog_product_index_eav';
    const TABLE_CATALOG_PRODUCT_INDEX_EAV_DECIMAL = 'catalog_product_index_eav_decimal';
    const TABLE_CATALOG_PRODUCT_INDEX_PRICE = 'catalog_product_index_price';

    /** @var StoreManagerInterface */
    protected $storeManager;

    /** @var ResourceConnection */
    protected $resource;

    /** @var ProductCollection */
    protected $collection;

    /**
     * CollectionMirror constructor.
     * @param ResourceConnection $resource
     * @param StoreManagerInterface $storeManager
     * @param ProductCollectionFactory $collectionFactory
     */
    public function __construct(
        ResourceConnection $resource,
        StoreManagerInterface $storeManager,
        ProductCollectionFactory $collectionFactory
    ) {
        $this->storeManager = $storeManager;
        $this->resource = $resource;
        $this->collection = $collectionFactory->create();
    }

    /**
     * Apply attribute filter to collection
     *
     * @param Attribute $attribute
     * @param $attributeValue
     */
    public function addAttributeFilter(Attribute $attribute, $attributeValue)
    {
        // Interpret attributeValue
        if (is_string($attributeValue) && strpos($attributeValue, ',') !== false) {
            $attributeValue = explode(',', $attributeValue);
        }
        $connection = $this->getConnection();
        $tableAlias = $this->getTableAlias($attribute);
        $conditions = [
            "{$tableAlias}.entity_id = e.entity_id",
            $connection->quoteInto("{$tableAlias}.attribute_id = ?", $attribute->getAttributeId()),
            $connection->quoteInto("{$tableAlias}.store_id = ?", $this->collection->getStoreId()),
        ];
        $conditions[] = is_array($attributeValue)
            ? $connection->quoteInto("{$tableAlias}.value IN (?)", $attributeValue)
            : $connection->quoteInto("{$tableAlias}.value = ?", $attributeValue);

        $this->collection->getSelect()->join(
            [$tableAlias => self::TABLE_CATALOG_PRODUCT_INDEX_EAV],
            implode(' AND ', $conditions),
            []
        );
    }

    /**
     * Add category filter to collection
     *
     * @param Category $category
     */
    public function addCategoryFilter(Category $category)
    {
        $this->collection->setStoreId($category->getStoreId())
            ->addCategoryFilter($category);
    }

    /**
     * Add decimal filter to collection
     *
     * @param Attribute $attribute
     * @param $from
     * @param $to
     */
    public function addDecimalFilter(Attribute $attribute, $from, $to)
    {
        $connection = $this->getConnection();
        $tableAlias = $this->getTableAlias($attribute);
        $conditions = [
            "{$tableAlias}.entity_id = e.entity_id",
            $connection->quoteInto("{$tableAlias}.attribute_id = ?", $attribute->getAttributeId()),
            $connection->quoteInto("{$tableAlias}.store_id = ?", $this->collection->getStoreId()),
        ];

        $this->collection->getSelect()->join(
            [$tableAlias => $this->getMainTable()],
            implode(' AND ', $conditions),
            []
        );

        $this->collection->getSelect()->where(
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
     * @param $from
     * @param $to
     */
    public function addPriceFilter($from, $to)
    {
        if ($from === '' && $to === '') {
            return;
        }
        $this->collection->addPriceData();
        $select = $this->collection->getSelect();
        if ($to !== '') {
            $to = (double)$to;
            if ($from == $to) {
                $to += CatalogPriceResource::MIN_POSSIBLE_PRICE;
            }
        }
        $priceExpr = $this->collection->getPriceExpression($select);
        if ($from !== '') {
            $select->where($priceExpr . ' >= ' . $this->_getComparingValue($from));
        }
        if ($to !== '') {
            $select->where($priceExpr . ' < ' . $this->_getComparingValue($to));
        }
    }

    /**
     * Get faceted data
     *
     * @param Attribute $attribute
     * @param $attributeValue
     * @return array
     */
    public function getFacetedData(Attribute $attribute, $attributeValue)
    {
        // Reset select, remove applied attribute filter
        $select = clone $this->collection->getSelect();
        $tableAlias = $this->getTableAlias($attribute);
        $this->resetSelect($select, [$tableAlias]);
        $connection = $this->getConnection();
        // Interpret attributeValue
        if (is_string($attributeValue) && strpos($attributeValue, ',') !== false) {
            $attributeValue = explode(',', $attributeValue);
        }
        $multiValueCond = is_array($attributeValue)
            ? $connection->quoteInto("value = {$tableAlias}.value OR value IN (?)", $attributeValue)
            : $connection->quoteInto("value = {$tableAlias}.value OR value = ?", $attributeValue);
        // TODO see issue #6 this is broken
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
     * Get comparing value sql part
     *
     * @param float $price
     * @param bool $decrease
     * @return float
     */
    protected function _getComparingValue($price, $decrease = true)
    {
        $currencyRate = $this->collection->getCurrencyRate();
        if ($decrease) {
            return ($price - CatalogPriceResource::MIN_POSSIBLE_PRICE / 2) / $currencyRate;
        }
        return ($price + CatalogPriceResource::MIN_POSSIBLE_PRICE / 2) / $currencyRate;
    }

    /**
     * Get connection
     *
     * @return \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected function getConnection()
    {
        return $this->resource->getConnection();
    }

    /**
     * Reset columns, order, limitation, and optionally 'from' tables from previous joins
     *
     * @param Select $select
     * @param string[] $from
     */
    protected function resetSelect(Select $select, $from = [])
    {
        $select->reset(Select::COLUMNS);
        $select->reset(Select::ORDER);
        $select->reset(Select::LIMIT_COUNT);
        $select->reset(Select::LIMIT_OFFSET);
        $fromPart = $select->getPart(Select::FROM);
        foreach($from as $tableAlias) {
            unset($fromPart[$tableAlias]);
        }
        $select->setPart(Select::FROM, $fromPart);
    }

    /**
     * Get table alias for attribute filter
     *
     * @param Attribute $attribute
     * @return string
     */
    protected function getTableAlias(Attribute $attribute)
    {
        return sprintf('%s_idx', $attribute->getAttributeCode());
    }
}
