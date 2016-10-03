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
     * Check that at least one dependent option is met by applied options or implicitly
     *
     * @param $dependentOptions
     * @param $appliedOptions
     * @return bool
     */
    protected function checkNonEmptyIntersection($dependentOptions, $appliedOptions)
    {
        // First check if any options are applied in state
        $applied = (bool)array_intersect($dependentOptions, $appliedOptions);

        // Check implicit values in collection (short circuit if any are found)
        return array_reduce($dependentOptions, function($met, $opt) {
            return $met || $this->isImplicitlyApplied($opt);
        }, $applied);
    }

    /**
     * Check that all dependent options are met by applied options or implicitly
     *
     * @param $dependentOptions
     * @param $appliedOptions
     * @return bool
     */
    protected function checkFullIntersection($dependentOptions, $appliedOptions)
    {
        // Get dependency options not applied in state
        $unmetOptions = array_diff($dependentOptions, $appliedOptions);

        // Check that all unmet dependencies are met implicitly (short circuit if any are not met)
        return array_reduce($unmetOptions, function($met, $opt) {
            return $met && $this->isImplicitlyApplied($opt);
        }, true);
    }

    /**
     * Check if all products in current layer collection have value $opt
     *
     * @param int $opt
     * @return bool
     */
    protected function isImplicitlyApplied($opt)
    {
        // Construct comma separated values from eav_index table
        $conn = $this->layer->getProductCollection()->getConnection();
        $innerTable = $conn->select()
            ->from('catalog_product_index_eav', 'entity_id')
            ->group('entity_id');
        $this->dbHelper->addGroupConcatColumn($innerTable, 'values', ['value']);

        // Get current layer select but reset columns
        // Basically doing work of calling getAllIds but in one query instead of two
        $select = clone $this->layer->getProductCollection()->getSelect();
        $select->reset(Select::COLUMNS)
            ->reset(Select::ORDER)
            ->reset(Select::LIMIT_COUNT)
            ->reset(Select::LIMIT_OFFSET)
            ->join(
                ['eav_index' => $innerTable],
                'e.entity_id = eav_index.entity_id',
                []
            )->columns(
                'COUNT(DISTINCT e.entity_id)'
            )->where(
                'NOT ' . $conn->prepareSqlCondition('eav_index.values', ['finset' => $opt])
            );
        // Check if any of the products in collection do NOT have value $opt
        return $conn->fetchOne($select) == 0;
    }
}
