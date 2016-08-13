<?php
/**
 * @package     BlueAcorn\LayeredNavigation
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\LayeredNavigation\Plugin;

use BlueAcorn\LayeredNavigation\Model\FacetPool;
use Magento\Catalog\Model\Category;
use Magento\CatalogSearch\Model\Layer\Category\ItemCollectionProvider;

class FacetInitializer
{
    /** @var FacetPool */
    protected $facetPool;

    /**
     * FacetInitializer constructor.
     * @param FacetPool $facetPool
     */
    public function __construct(FacetPool $facetPool)
    {
        $this->facetPool = $facetPool;
    }

    /**
     * Applies initial category filter to facet pool
     *
     * @param Category $category
     */
    public function beforeGetCollection(ItemCollectionProvider $subject, Category $category)
    {
        // TODO magento doesn't apply filter in search context (in this case $category is root category)
        $this->facetPool->addCategoryFilter($category);
    }
}
