<?php
/**
 * @package     BlueAcorn\LayeredNavigation
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\LayeredNavigation\Model\ResourceModel;

use Magento\Catalog\Model\Layer\FilterableAttributeListInterface;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;
use BlueAcorn\LayeredNavigation\Model\Dependency as DependencyModel;
use Magento\Store\Model\Store;

class Dependency extends AbstractDb
{
    const FILTER_DEPENDENCY_MAIN_TABLE = 'ba_layerednav_filter_dependency';
    const FILTER_DEPENDENCY_STORE_TABLE = 'ba_layerednav_filter_dependency_store';

    /** @var FilterableAttributeListInterface */
    protected $filterableAttributeList;

    /**
     * Dependency constructor.
     * @param FilterableAttributeListInterface $filterableAttributeList
     * @param Context $context
     * @param $connectionName
     */
    public function __construct(
        FilterableAttributeListInterface $filterableAttributeList,
        Context $context,
        $connectionName = null
    ) {
        parent::__construct($context, $connectionName);
        $this->filterableAttributeList = $filterableAttributeList;
    }

    /**
     * Set main table and id field name
     */
    protected function _construct()
    {
        $this->_init(self::FILTER_DEPENDENCY_MAIN_TABLE, 'dependency_id');
    }

    /**
     * Load attribute model onto dependency model
     *
     * @param DependencyModel $dependency
     * @throws \InvalidArgumentException
     * @return $this
     */
    public function loadAttributeData(DependencyModel $dependency)
    {
        $attributeId = $dependency->getFilterAttributeId();
        if ($attributeId) {
            $attribute = $this->filterableAttributeList->getList()
                ->addFieldToFilter('main_table.attribute_id', $attributeId)
                ->getFirstItem();
            if ($attribute->getId()) {
                $dependency->setFilterAttribute($attribute);
            } else {
                throw new \InvalidArgumentException('Attribute ID either does not exist or is not filterable');
            }
        }

        return $this;
    }

    /**
     * Get store ids to which specified item is assigned
     *
     * @param int $id
     * @return array
     */
    public function lookupStoreIds($id)
    {
        $connection = $this->getConnection();

        $select = $connection->select()->from(
            $this->getTable(self::FILTER_DEPENDENCY_STORE_TABLE),
            'store_id'
        )->where(
            'dependency_id = ?',
            (int)$id
        );

        return $connection->fetchCol($select);
    }

    /**
     * Initialize unique fields
     *
     * @return $this
     */
    protected function _initUniqueFields()
    {
        $this->_uniqueFields = [
            ['field' => ['attribute_id', 'option_id'], 'title' => __('Dependency already exists')],
        ];
        return $this;
    }

    /**
     * Delete store links before deleting dependency entity
     *
     * @param AbstractModel $object
     * @return $this
     */
    protected function _beforeDelete(AbstractModel $object)
    {
        $condition = ['dependency_id = ?' => (int)$object->getId()];
        $this->getConnection()->delete($this->getTable(self::FILTER_DEPENDENCY_STORE_TABLE), $condition);
        return parent::_beforeDelete($object);
    }

    /**
     * Update store table after save
     *
     * @param AbstractModel $object
     * @return $this
     */
    protected function _afterSave(AbstractModel $object)
    {
        $oldStores = $this->lookupStoreIds($object->getId());
        $newStores = (array)$object->getStores();

        $table = $this->getTable(self::FILTER_DEPENDENCY_STORE_TABLE);
        $insert = array_diff($newStores, $oldStores);
        $delete = array_diff($oldStores, $newStores);

        if ($delete) {
            $where = ['dependency_id = ?' => (int)$object->getId(), 'store_id IN (?)' => $delete];
            $this->getConnection()->delete($table, $where);
        }

        if ($insert) {
            $insertData = [];
            foreach ($insert as $storeId) {
                $insertData[] = ['dependency_id' => (int)$object->getId(), 'store_id' => (int)$storeId];
            }
            $this->getConnection()->insertMultiple($table, $insertData);
        }

        return parent::_afterSave($object);
    }

    /**
     * Add stores after load
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @return $this
     */
    protected function _afterLoad(\Magento\Framework\Model\AbstractModel $object)
    {
        if ($object->getId()) {
            $stores = $this->lookupStoreIds($object->getId());
            $object->setData('store_id', $stores);
            $object->setData('stores', $stores);
        }

        return parent::_afterLoad($object);
    }

    /**
     * Limit store scope on load if store_id is set on object
     *
     * @param string $field
     * @param mixed $value
     * @param AbstractModel $object
     * @return \Magento\Framework\DB\Select
     */
    protected function _getLoadSelect($field, $value, $object)
    {
        $select = parent::_getLoadSelect($field, $value, $object);

        if ($object->getStoreId()) {
            $stores = [(int)$object->getStoreId(), Store::DEFAULT_STORE_ID];
            $select->join(
                ['ds' => $this->getTable(self::FILTER_DEPENDENCY_STORE_TABLE)],
                $this->getMainTable() . '.dependency_id = ds.dependency_id',
                ['store_id']
            )->where(
                'status = ?',
                1
            )->where(
                'ds.store_id in (?)',
                $stores
            )->order(
                'store_id DESC'
            )->limit(
                1
            );
        }

        return $select;
    }
}
