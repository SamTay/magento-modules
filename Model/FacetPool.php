<?php
/**
 * @package     BlueAcorn\LayeredNavigation
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\LayeredNavigation\Model;

use Magento\Catalog\Model\Layer;
use Magento\Catalog\Model\Layer\Resolver as LayerResolver;
use Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection as FulltextCollection;

/**
 * Class FacetPool
 * TODO: FUCK looks like catalog_view_container does not exist when trying to query against multiple fulltext collections
 * TODO: Possibly use normal catalog resource collections per facet, and keep main facet in line with fulltext collection
 * ( if getting faceted data per collection doesn't work )
 */
class FacetPool
{
    /** TODO Decide whether or not to use catalog resource collection or catalog-search fulltext collection */
    /** @var \Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection[] */
    protected $facets = [];

    /** @var array filters for which we should NOT clone collections */
    protected $filterblacklist = ['category_ids', 'visibility'];

    /** TODO Decide whether or not to use catalog resource collection or catalog-search fulltext collection */
    protected $collectionFactory;

    /** @var Layer */
    protected $layer;

    /**
     * CollectionPool constructor.
     * @param Layer $layer
     */
    public function __construct(
        LayerResolver $layerResolver
    ) {
        $this->layer = $layerResolver->get();
    }

    /**
     * @param $attributeCode
     * @param FulltextCollection $collection
     */
    public function addFacet($attributeCode, FulltextCollection $collection)
    {
        // TODO test if clone works for collections, otherwise preference rewrite, __call, and save [method, args]
        // TODO what should happen if array key already exists??
        if (!in_array($attributeCode, $this->filterblacklist)) {
            $this->facets[$attributeCode] = clone $collection;
        }
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
                // Direct call to 'addFieldToFilter' so that we don't hook into plugin again
                $className = 'Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection';
                $method = 'addFieldToFilter';
                call_user_func([$collection, "$className::$method"], $field, $condition);
            }
        }
    }
}
