<?php
/**
 * @package     BlueAcorn\LayeredNavigation
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\LayeredNavigation\Model\ResourceModel\Dependency;

use BlueAcorn\LayeredNavigation\Model\ResourceModel\Dependency as ResourceModel;
use Magento\Framework\DB\Select;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Store\Model\Store;

class Collection extends AbstractCollection
{
    /** @var \Magento\Store\Model\StoreManagerInterface */
    protected $storeManager;

    /**
     * @param \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\DB\Adapter\AdapterInterface|null $connection
     * @param \Magento\Framework\Model\ResourceModel\Db\AbstractDb|null $resource
     */
    public function __construct(
        \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\DB\Adapter\AdapterInterface $connection = null,
        \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource = null
    ) {
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $connection, $resource);
        $this->storeManager = $storeManager;
    }

    /**
     * Initialize with model/resource class names
     */
    protected function _construct()
    {
        $this->_init(
            'BlueAcorn\LayeredNavigation\Model\Dependency',
            'BlueAcorn\LayeredNavigation\Model\ResourceModel\Dependency'
        );
        $this->_map['fields']['dependency_id'] = 'main_table.dependency_id';
        $this->_map['fields']['store'] = 'store_table.store_id';
    }

    /**
     * Add store data to each item
     *
     * @return $this
     */
    protected function _afterLoad()
    {
        $items = $this->getColumnValues('dependency_id');
        if (count($items)) {
            $connection = $this->getConnection();
            $select = $connection->select()->from(['store_table' => ResourceModel::FILTER_DEPENDENCY_STORE_TABLE])
                ->where('store_table.dependency_id IN (?)', $items);
            $result = $connection->fetchPairs($select);
            if ($result) {
                foreach ($this as $item) {
                    $entityId = $item->getDependencyId();
                    if (!isset($result[$entityId])) {
                        continue;
                    }
                    if ($result[$entityId] == 0) {
                        $stores = $this->storeManager->getStores(false, true);
                        $storeId = current($stores)->getId();
                        $storeCode = key($stores);
                    } else {
                        $storeId = $result[$item->getDependencyId()];
                        $storeCode = $this->storeManager->getStore($storeId)->getCode();
                    }
                    $item->setData('_first_store_id', $storeId);
                    $item->setData('store_code', $storeCode);
                    $item->setData('store_id', [$result[$entityId]]);
                }
            }
        }
        return parent::_afterLoad();
    }

    /**
     * Handle adding store filter
     *
     * @param array|string $field
     * @param string|int|array|null $condition
     * @return $this
     */
    public function addFieldToFilter($field, $condition = null)
    {
        if ($field === 'store_id') {
            return $this->addStoreFilter($condition, false);
        }
        return parent::addFieldToFilter($field, $condition);
    }

    /**
     * Add store filter
     *
     * @param int|array|Store $store
     * @param bool $withAdmin
     * @return $this
     */
    public function addStoreFilter($store, $withAdmin = true)
    {
        if ($store instanceof Store) {
            $store = [$store->getId()];
        }
        if (!is_array($store)) {
            $store = [$store];
        }
        if ($withAdmin) {
            $store[] = Store::DEFAULT_STORE_ID;
        }

        $this->addFilter('store', ['in' => $store], 'public');
    }

    /**
     * Join store table if necessary
     */
    protected function _renderFiltersBefore()
    {
        if ($this->getFilter('store')) {
            $this->getSelect()->join(
                ['store_table' => $this->getTable(ResourceModel::FILTER_DEPENDENCY_STORE_TABLE)],
                'main_table.dependency_id = store_table.dependency_id',
                []
            )->group(
                'main_table.dependency_id'
            );
        }
        parent::_renderFiltersBefore();
    }

    /**
     * Strip group by for counting
     *
     * @return Select
     */
    public function getSelectCountSql()
    {
        $countSelect = parent::getSelectCountSql();
        $countSelect->reset(Select::GROUP);

        return $countSelect;
    }
}
