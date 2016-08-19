<?php
/**
 * @package     BlueAcorn\LayeredNavigation
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\LayeredNavigation\Model\Layer\Filter;

/**
 * Override from native to hook into collection mirror.
 * Right now, restricting multi value sorting to select/multiselect attributes
 */
class Category extends \Magento\CatalogSearch\Model\Layer\Filter\Category
{
    /** @var \BlueAcorn\LayeredNavigation\Model\Layer\CollectionMirror */
    protected $collectionMirror;

    /**
     * @param \Magento\Catalog\Model\Layer\Filter\ItemFactory $filterItemFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Catalog\Model\Layer $layer
     * @param \Magento\Catalog\Model\Layer\Filter\Item\DataBuilder $itemDataBuilder
     * @param \Magento\Framework\Escaper $escaper
     * @param \Magento\Catalog\Model\Layer\Filter\DataProvider\CategoryFactory $categoryDataProviderFactory
     * @param \BlueAcorn\LayeredNavigation\Model\Layer\CollectionMirror $collectionMirror
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Model\Layer\Filter\ItemFactory $filterItemFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\Layer $layer,
        \Magento\Catalog\Model\Layer\Filter\Item\DataBuilder $itemDataBuilder,
        \Magento\Framework\Escaper $escaper,
        \Magento\Catalog\Model\Layer\Filter\DataProvider\CategoryFactory $categoryDataProviderFactory,
        \BlueAcorn\LayeredNavigation\Model\Layer\CollectionMirror $collectionMirror,
        array $data = []
    ) {
        parent::__construct(
            $filterItemFactory,
            $storeManager,
            $layer,
            $itemDataBuilder,
            $escaper,
            $categoryDataProviderFactory,
            $data
        );
        $this->collectionMirror = $collectionMirror;
    }

    /**
     * Apply category filter to product collection
     *
     * @param   \Magento\Framework\App\RequestInterface $request
     * @return  $this
     */
    public function apply(\Magento\Framework\App\RequestInterface $request)
    {
        parent::apply($request);
        /** check if parent method applied filter */
        if ($appliedFilter = $this->getLayer()->getState()->hasFilter($this)) {
            $category = $this->getCategory();
            $this->collectionMirror->addCategoryFilter($category);
        }
        return $this;
    }

    /**
     * Get category from parent private property (better than reloading category)
     * TODO Needs testing fo sho
     *
     * @return mixed
     */
    private function getCategory()
    {
        $categoryGetter = (function() {
            /** @var $this \Magento\CatalogSearch\Model\Layer\Filter\Category */
            return $this->dataProvider->getCategory();
        })->bindTo($this, '\Magento\CatalogSearch\Model\Layer\Filter\Category');

        return $categoryGetter();
    }
}
