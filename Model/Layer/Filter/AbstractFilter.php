<?php
/**
 * @package     BlueAcorn\LayeredNavigation
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\LayeredNavigation\Model\Layer\Filter;

use BlueAcorn\LayeredNavigation\Model\Layer\FilterDependency\Manager as FilterDependencyManager;

/**
 * Layer category filter abstract model
 */
abstract class AbstractFilter extends \Magento\Catalog\Model\Layer\Filter\AbstractFilter
{
    /** @var FilterDependencyManager */
    protected $filterDependencyManager;

    /**
     * AbstractFilter constructor.
     * @param \Magento\Catalog\Model\Layer\Filter\ItemFactory $filterItemFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Catalog\Model\Layer $layer
     * @param \Magento\Catalog\Model\Layer\Filter\Item\DataBuilder $itemDataBuilder
     * @param FilterDependencyManager $filterDependencyManager
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Model\Layer\Filter\ItemFactory $filterItemFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\Layer $layer,
        \Magento\Catalog\Model\Layer\Filter\Item\DataBuilder $itemDataBuilder,
        FilterDependencyManager $filterDependencyManager,
        array $data
    ) {
        parent::__construct($filterItemFactory,
            $storeManager,
            $layer,
            $itemDataBuilder,
            $data
        );
        $this->filterDependencyManager = $filterDependencyManager;
    }

    /**
     * Check if filter should be rendered
     * (Native would just check item count -- we introduce an additional check against filter dependencies)
     *
     * @return bool
     */
    public function isVisible()
    {
        if (!$this->getItemsCount()) {
            return false;
        }
        return $this->filterDependencyManager->isVisible($this);
    }
}
