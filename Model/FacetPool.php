<?php
/**
 * @package     BlueAcorn\LayeredNavigation
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\LayeredNavigation\Model;

use Magento\Catalog\Model\Layer;
use Magento\Catalog\Model\Layer\Filter\FilterInterface;
use Magento\Catalog\Model\Layer\Resolver as LayerResolver;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;

/**
 * Class FacetPool
 */
class FacetPool
{
    /** @var Layer */
    protected $layer;

    /** @var FacetBuilder */
    protected $facetBuilder;

    /** @var ProductCollectionFactory */
    protected $collectionFactory;

    /** @var Facet[] */
    protected $facets = [];

    /** @var array */
    protected $methodStack;

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

    /**
     * Add facet to pool based on current layer collection
     * TODO This might not be the right approach... excluding the attribute A=>1 filter and counting
     * results for attribute A=>2 is not the same as counting attribute A=>1 OR A=>2. Shiet
     *
     * @param Attribute $attribute
     */
    public function addFacet(Attribute $attribute)
    {
        // TODO what should happen if array key already exists??
        // TODO possibly filter against existence in FilterableAttributesList
        // TODO maybe check if $field is a non price/decimal attribute ?? Not sure ...
        // if all price/decimals are using sliders then we shouldn't worry about faceting against them
        $this->facets[$attribute->getAttributeCode()] = $this->facetBuilder
            ->setCollection($this->mirrorCollection())
            ->setAttribute($attribute)
            ->create();
    }

    /**
     * Get layer product collection without specified attribute filter
     *
     * @param $attributeCode
     * @return Facet
     */
    public function getFacet($attributeCode)
    {
        return array_key_exists($attributeCode, $this->facets) ? $this->facets[$attributeCode] : null;
    }

    /**
     * Add collection modifier to methodStack and apply to all current facets
     *
     * @param $method
     * @param $args
     */
    public function addCollectionModifier($method, $args = [])
    {
        // modify current facets
        foreach($this->facets as $facet) {
            if (!$facet->shouldSkip($method, $args)) {
                call_user_func_array([$facet->getCollection(), $method], $args);
            }
        }
        $this->addToModifierStack($method, $args);
    }

    /**
     * Create a new collection that mirrors the current layer's fulltext collection
     *
     * @return ProductCollection
     */
    protected function mirrorCollection()
    {
        $collection = $this->collectionFactory->create();
        foreach($this->methodStack as $func) {
            call_user_func_array([$collection, $func['method']], $func['args']);
        }
        return $collection;
    }

    /**
     * Add modifier to method stack
     *
     * @param $method
     * @param $args
     */
    protected function addToModifierStack($method, $args)
    {
        if (!$this->isBlacklisted($method, $args)) {
            $this->methodStack[] = ['method' => $method, 'args' => $args];
        }
    }

    /**
     * Check if modifier is blacklisted
     * TODO refactor this loop through array property of callables
     *
     * @param $method
     * @param $args
     * @return bool
     */
    protected function isBlacklisted($method, $args)
    {
        switch(true) {
            case ($method == 'addFieldToFilter' && $args[0] == 'category_ids'):
                return true;
            case ($method == 'addFieldToFilter' && $args[0] == 'visibility'):
                return true;
            default:
                return false;
        }
    }
}
