<?php
/**
 * @package     BlueAcorn\LayeredNavigation
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\LayeredNavigation\Model;

use BlueAcorn\LayeredNavigation\Api\DependencyManagerInterface;
use BlueAcorn\LayeredNavigation\Helper\Config;
use BlueAcorn\LayeredNavigation\Model\ResourceModel\Dependency\CollectionFactory;
use Magento\Catalog\Model\Layer\State;
use Magento\Framework\Db\Helper as DbHelper;
use Magento\Framework\Db\Select;

class DependencyManager implements DependencyManagerInterface
{
    /** @var CollectionFactory */
    protected $collectionFactory;

    /** @var DbHelper */
    protected $dbHelper;

    /** * @var Config */
    protected $config;

    /**
     * DependencyManager constructor.
     * @param CollectionFactory $collectionFactory
     * @param DbHelper $dbHelper
     * @param Config $config
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        DbHelper $dbHelper,
        Config $config
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->dbHelper = $dbHelper;
        $this->config = $config;
    }

    /**
     * Get array of attribtue IDs with unmet dependencies
     *
     * @param State $state
     * @return int[]
     */
    public function getUnmetDependencies(State $state)
    {
        $stateOptions = $this->getAllStateOptions($state);
        $join = $this->config->getDependencyJoin();
        $diffMethod = $this->getDiffMethod($join);
        $collection = $this->collectionFactory->create()
            ->addFieldToSelect('attribute_id')
            ->addFieldToFilter('status', 1);
        $select = $collection->getSelect();
        $this->dbHelper->addGroupConcatColumn($select, 'option_ids', ['option_id'])
            ->group('attribute_id');

        return array_filter(array_map(function($dependency) use($stateOptions, $diffMethod) {
            $dependentOptions = explode(',', $dependency->getOptionIds());
            return call_user_func($diffMethod, $dependentOptions, $stateOptions) ? null : $dependency->getAttributeId();
        }, $collection->getItems()));
    }

    /**
     * Get array of all applied option_id's
     *
     * @param State $state
     * @return array
     */
    protected function getAllStateOptions(State $state)
    {
        $options = [];
        foreach($state->getFilters() as $filterItem) {
            /** @var \Magento\Catalog\Model\ResourceModel\Eav\Attribute|null $attribute */
            $attribute = $filterItem->getFilter()->getData('attribute_model'); // Avoid exceptions on category filters
            if (!$attribute || !$attribute->usesSource()) {
                continue;
            }
            $options = array_merge($options, explode(',', $filterItem->getValueString()));
        }
        return $options;
    }

    /**
     * Get array diff method
     *
     * @param $join
     * @return callable
     */
    protected function getDiffMethod($join)
    {
        switch ($join) {
            case Select::SQL_AND:
                return [$this, 'checkFullIntersection'];
            case Select::SQL_OR:
                return [$this, 'checkNonEmptyIntersection'];
        }
    }

    /**
     * @param $arrayA
     * @param $arrayB
     * @return bool
     */
    protected function checkNonEmptyIntersection($arrayA, $arrayB)
    {
        return (bool)array_intersect($arrayA, $arrayB);
    }

    /**
     * @param $arrayA
     * @param $arrayB
     * @return bool
     */
    protected function checkFullIntersection($arrayA, $arrayB)
    {
        return !array_diff($arrayA, $arrayB);
    }
}
