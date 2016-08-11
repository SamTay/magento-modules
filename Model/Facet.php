<?php
/**
 * @package     BlueAcorn\LayeredNavigation
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\LayeredNavigation\Model;

use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;

class Facet
{
    /** @var string */
    protected $attributeCode;

    /** @var  ProductCollection */
    protected $collection;

    /** @var AdapterInterface */
    protected $connection;

    public function __construct(
        ProductCollection $collection,
        AdapterInterface $connection,
        $attributeCode
    ) {
        $this->attributeCode = $attributeCode;
        $this->collection = $collection;
        $this->connection = $connection;
    }

    /**
     * Get result count for this facet
     *
     * @return int
     */
    public function getCount()
    {
        // clone select from collection
        $select = clone $this->collection->getSelect();
        // reset columns, order and limitation conditions
        $select->reset(Select::COLUMNS);
        $select->reset(Select::ORDER);
        $select->reset(Select::LIMIT_COUNT);
        $select->reset(Select::LIMIT_OFFSET);

        $connection = $this->getConnection();
        $attribute = $filter->getAttributeModel();
        $tableAlias = sprintf('%s_idx', $attribute->getAttributeCode());
        $conditions = [
            "{$tableAlias}.entity_id = e.entity_id",
            $connection->quoteInto("{$tableAlias}.attribute_id = ?", $attribute->getAttributeId()),
            $connection->quoteInto("{$tableAlias}.store_id = ?", $filter->getStoreId()),
        ];

        $select->join(
            [$tableAlias => $this->getMainTable()],
            join(' AND ', $conditions),
            ['value', 'count' => new \Zend_Db_Expr("COUNT({$tableAlias}.entity_id)")]
        )->group(
            "{$tableAlias}.value"
        );

        return $connection->fetchPairs($select);
    }

    /**
     * Forward filter to colletion
     *
     * @param mixed $field
     * @param mixed $condition
     */
    public function addFieldToFilter($field, $condition = null)
    {
        $this->collection->addFieldToFilter($field, $condition);
    }

    /**
     * Get attribute code
     *
     * @return string
     */
    public function getAttributeCode()
    {
        return $this->attributeCode;
    }

    /**
     * Get connection
     *
     * @return AdapterInterface
     */
    public function getConnection()
    {
        return $this->connection;
    }
}
