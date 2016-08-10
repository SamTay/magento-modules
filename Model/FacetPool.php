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
    const MAIN = '__MAIN__';

    /** @var ProductCollection[] */
    protected $facets = [];

    /** @var ProductCollectionFactory */
    protected $collectionFactory;

    /** @var Layer */
    protected $layer;

    /** @var array filters for which we should NOT create collections */
    protected $filterblacklist = ['category_ids', 'visibility'];

    /**
     * CollectionPool constructor.
     * @param LayerResolver $layerResolver
     * @param ProductCollectionFactory $collectionFactory
     */
    public function __construct(
        LayerResolver $layerResolver,
        ProductCollectionFactory $collectionFactory
    ) {
        $this->layer = $layerResolver->get();
        $this->collectionFactory = $collectionFactory;
        $this->facets[self::MAIN] = $this->collectionFactory->create();
    }

    public function addFacet($field, $condition)
    {
        // TODO test if clone works for collections, otherwise preference rewrite, __call, and save [method, args]
        // TODO what should happen if array key already exists??
        // TODO possibly filter $field against attributes of type int / varchar or select / multiselect, etc.
        // TODO maybe check if $field is a non price/decimal attribute ?? Not sure ...
        // if all price/decimals are using sliders then we shouldn't worry about faceting against them
        if (!in_array($field, $this->filterblacklist)) {
            $this->facets[$field] = clone $this->facets[self::MAIN];
        }
        $this->addFieldToFilter($field, $condition);
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
    public function addFieldToFilter($field, $condition)
    {
        foreach ($this->facets as $attributeCode => $collection) {
            if ($attributeCode != $field) {
                $collection->addFieldToFilter($field, $condition);
            }
        }
    }
}
