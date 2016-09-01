<?php
/**
 * @package     BlueAcorn\LayeredNavigation
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\LayeredNavigation\Model\Dependency;

use Magento\Catalog\Model\Layer\FilterableAttributeListInterface;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class FilterAttributesList
 * Native implementations are either category or search -- this implementation includes both
 */
class FilterAttributesList implements FilterableAttributeListInterface
{
    /** @var CollectionFactory */
    protected $collectionFactory;

    /** @var StoreManagerInterface */
    protected $storeManager;

    /** @var array|null */
    protected $options;

    /**
     * FilterableAttributeList constructor
     *
     * @param CollectionFactory $collectionFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * Retrieve list of filterable attributes
     *
     * @return array|\Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection
     */
    public function getList()
    {
        /** @var $collection \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection */
        $collection = $this->collectionFactory->create();
        $collection->setItemObjectClass('Magento\Catalog\Model\ResourceModel\Eav\Attribute')
            ->addStoreLabel($this->storeManager->getStore()->getId())
            ->setOrder('position', 'ASC');
        $this->_prepareAttributeCollection($collection);

        return $collection;
    }

    /**
     * Add filters to attribute collection
     *
     * @param \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection $collection
     * @return \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection
     */
    protected function _prepareAttributeCollection($collection)
    {
        $collection->addFieldToFilter(
            ['additional_table.is_filterable', 'additional_table.is_filterable_in_search'],
            [['gt' => 0], ['gt' => 0]]
        );
        return $collection;
    }
}
