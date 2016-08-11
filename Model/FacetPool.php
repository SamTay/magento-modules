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
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;

/**
 * Class FacetPool
 */
class FacetPool
{
    /** @var Facet[] */
    protected $facets = [];

    /** @var Layer */
    protected $layer;

    /** @var array filters for which we should NOT create facets */
    protected $filterblacklist = ['category_ids', 'visibility'];

    /** @var FacetBuilder */
    protected $facetBuilder;

    /** @var array */
    protected $filterStack;

    /** @var ProductCollectionFactory */
    protected $collectionFactory;

    /**
     * CollectionPool constructor.
     * @param LayerResolver $layerResolver
     * @param ProductCollectionFactory $collectionFactory
     * @param FacetBuilder $facetBuilder
     */
    public function __construct(
        LayerResolver $layerResolver,
        ProductCollectionFactory $collectionFactory,
        FacetBuilder $facetBuilder
    ) {
        $this->layer = $layerResolver->get();
        $this->collectionFactory = $collectionFactory;
        $this->facetBuilder = $facetBuilder;
    }

    public function addFacet($field, $condition)
    {
        // TODO what should happen if array key already exists??
        // TODO possibly filter $field against attributes of type int / varchar or select / multiselect, etc.
        // TODO maybe check if $field is a non price/decimal attribute ?? Not sure ...
        // if all price/decimals are using sliders then we shouldn't worry about faceting against them
        if (!in_array($field, $this->filterblacklist)) {
            $this->facets[$field] = $this->mirrorCollection();
        }
        $this->addFilterToFacets($field, $condition);
        $this->addFilterToStack($field, $condition);
    }

    /**
     * Get layer product collection without specified attribute filter
     *
     * @param $attributeCode
     * @return ProductCollection|null
     */
    public function getFacet($attributeCode)
    {
        return array_key_exists($attributeCode, $this->facets) ? $this->facets[$attributeCode] : null;
    }

    /**
     * Add field filter to all but one facet -- this excluded facet will be used to get the possible additional
     * possible filter items for this attribute field (using OR logic)
     *
     * @param $field
     * @param $condition
     */
    public function addFilterToFacets($field, $condition)
    {
        foreach ($this->facets as $attributeCode => $collection) {
            if ($attributeCode != $field) {
                $collection->addFieldToFilter($field, $condition);
            }
        }
    }

    /**
     * Create a new collection that mirrors the current layer's fulltext collection
     *
     * @return ProductCollection
     */
    protected function mirrorCollection()
    {
        $collection = $this->collectionFactory->create();
        foreach($this->filterStack as $filterArgs) {
            call_user_func_array([$collection, 'addFieldToFilter'], $filterArgs);
        }
        return $collection;
    }

    /**
     * Add filter arguments to filter stack
     *
     * @param mixed $field
     * @param mixed $condition
     */
    protected function addFilterToStack($field, $condition = null)
    {
        $this->filterStack[] = [$field, $condition];
    }
}
