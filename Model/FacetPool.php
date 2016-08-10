<?php
/**
 * @package     BlueAcorn\LayeredNavigation
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\LayeredNavigation\Model;

use Magento\Catalog\Model\Layer;
use Magento\CatalogSearch\Model\ResourceModel\Fulltext\CollectionFactory as FulltextCollectionFactory;
use Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection as FulltextCollection;

class FacetPool
{
    /** TODO Decide whether or not to use catalog resource collection or catalog-search fulltext collection */
    /** @var \Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection[] */
    protected $facets = [];

    /** @var array filters for which we should NOT clone collections */
    protected $filterblacklist = [];

    /** TODO Decide whether or not to use catalog resource collection or catalog-search fulltext collection */
    /** @var FulltextCollectionFactory */
    protected $collectionFactory;

    /** @var Layer */
    protected $layer;

    /**
     * CollectionPool constructor.
     * @param FulltextCollectionFactory $collectionFactory
     * @param Layer $layer
     */
    public function __construct(
        FulltextCollectionFactory $collectionFactory,
        Layer $layer
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->layer = $layer;
    }

    /**
     * @param $attributeCode
     * @param FulltextCollection $collection
     */
    public function addFacet($attributeCode, FulltextCollection $collection)
    {
        // TODO test if clone works for collections, otherwise preference rewrite, __call, and save [method, args]
        // TODO what should happen if array key already exists??
        $this->facets[$attributeCode] = clone $collection;
    }

    /**
     * Get layer product collection without specified attribute filter
     *
     * @param $attributeCode
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection|FulltextCollection
     */
    public function getFacet($attributeCode)
    {
        // TODO decide whether to be strict (requires filter models knowing whether or not their field has been applied)
        // and coupled or loose coupling and defaulting to singleton layer collection
        return array_key_exists($attributeCode, $this->facets)
            ? $this->facets[$attributeCode]
            : $this->layer->getProductCollection();
    }

    /**
     * Add field filter to all but one facet -- this excluded facet will be used to get the possible additional
     * possible filter items for this attribute field (using OR logic)
     *
     * @param $field
     * @param $condition
     */
    public function addFieldToFilter($field, $condition)
    {
        foreach ($this->facets as $attributeCode => $collection) {
            if ($attributeCode != $field) {
                $collection->addFieldToFilter($field, $condition);
            }
        }
    }
}
