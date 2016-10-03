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
use Magento\Catalog\Model\Layer;
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

    /** @var null|Layer */
    protected $layer;

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
     * @param Layer $layer
     * @return \int[]
     */
    public function getUnmetDependencies(Layer $layer)
    {
        $this->layer = $layer;
        $stateOptions = $this->getAllStateOptions();
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
            return call_user_func($diffMethod, $dependentOptions, $stateOptions)
                ? null // filter out attributes with unmet dependencies
                : $dependency->getAttributeId();
        }, $collection->getItems()));
    }

    /**
     * Get array of all applied option_id's
     *
     * @return array
     */
    protected function getAllStateOptions()
    {
        $options = [];
        foreach($this->layer->getState()->getFilters() as $filterItem) {
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
     * @param $dependentOptions
     * @param $appliedOptions
     * @return bool
     */
    protected function checkNonEmptyIntersection($dependentOptions, $appliedOptions)
    {
        // First check state
        $applied = (bool)array_intersect($dependentOptions, $appliedOptions);
        if ($applied) {
            return true; // Dont query if we dont have to
        }
        return array_reduce($dependentOptions, function($met, $opt) {
            return $met |= $this->isImplicitlyApplied($opt);
        }, false);
    }

    /**
     * @param $dependentOptions
     * @param $appliedOptions
     * @return bool
     */
    protected function checkFullIntersection($arrayA, $arrayB)
    {
        return !array_diff($arrayA, $arrayB);
    }
}
