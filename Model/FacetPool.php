<?php
/**
 * @package     BlueAcorn\LayeredNavigation
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\LayeredNavigation\Model;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Layer;
use BlueAcorn\LayeredNavigation\Model\ResourceModel\Facet as FacetResource;
use Magento\Catalog\Model\Layer\Resolver as LayerResolver;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\Phrase;

/**
 * Class FacetPool
 * @method addAttributeFilter(Attribute $attribute, string $attributeValue)
 * @method addCategoryFilter(Category $category)
 * @method addDecimalFilter(Attribute $attribute, float|string $from, float|string $to)
 * @method addPriceFilter(float|string $from, float|string $to)
 */
class FacetPool
{
    /** @var Layer */
    protected $layer;

    /** @var FacetBuilder */
    protected $facetBuilder;

    /** @var ProductCollectionFactory */
    protected $collectionFactory;

    /** @var FacetResource */
    protected $resource;

    /** @var Facet[] */
    protected $facets = [];

    /** @var array */
    protected $methodStack = [];

    /** @var array */
    private $allowedMethods = [
        'addAttributeFilter',
        'addCategoryFilter',
        'addDecimalFilter',
        'addPriceFilter'
    ];


    /**
     * CollectionPool constructor.
     * @param LayerResolver $layerResolver
     * @param ProductCollectionFactory $collectionFactory
     * @param FacetBuilder $facetBuilder
     * @param FacetResource $resource
     */
    public function __construct(
        LayerResolver $layerResolver,
        ProductCollectionFactory $collectionFactory,
        FacetBuilder $facetBuilder,
        FacetResource $resource
    ) {
        $this->layer = $layerResolver->get();
        $this->collectionFactory = $collectionFactory;
        $this->facetBuilder = $facetBuilder;
        $this->resource = $resource;
    }

    /**
     * Add facet to pool based on current layer collection
     *
     * @param Attribute $attribute
     * @param $attributeValue
     */
    public function addFacet(Attribute $attribute, $attributeValue)
    {
        // TODO what should happen if array key already exists??
        // TODO possibly filter against existence in FilterableAttributesList
        // TODO maybe check if $field is a non price/decimal attribute ?? Not sure ...
        // if all price/decimals are using sliders then we shouldn't worry about faceting against them
        $facet = $this->facetBuilder
            ->setCollection($this->mirrorCollection())
            ->setAttribute($attribute)
            ->setAttributeValue($attributeValue)
            ->create();
        $this->addAttributeFilter($attribute, $attributeValue);
        $this->facets[$attribute->getAttributeCode()] = $facet; // Add to facet pool after attribute filter is applied
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
     * Update all current facets with the latest addition to methodStack
     */
    private function updateFacets()
    {
        $modifier = end($this->methodStack);
        if (!$modifier) {
            return;
        }
        $method = $modifier['method'];
        $args = $modifier['args'];
        foreach($this->facets as $attributeCode => $facet) {
            if ($this->shouldSkip($attributeCode, $method, $args)) {
                continue;
            }
            call_user_func_array(
                [$this->resource, $method],
                array_merge([$facet->getCollection()], $args)
            );
        }
    }

    /**
     * Create a new collection that mirrors the current layer's fulltext collection
     *
     * @return ProductCollection
     */
    private function mirrorCollection()
    {
        $collection = $this->collectionFactory->create();
        foreach($this->methodStack as $modifier) {
            call_user_func_array(
                [$this->resource, $modifier['method']],
                array_merge([$collection], $modifier['args'])
            );
        }
        return $collection;
    }

    /**
     * Magic call implemented for forwarding specific methods to facet resource
     *
     * @param $method
     * @param $arguments
     */
    public function __call($method, $arguments)
    {
        if (!in_array($method, $this->allowedMethods)) {
            throw new \BadMethodCallException(
                new Phrase('Invalid method %1::%2(%3)', [get_class($this), $method, print_r($arguments, 1)])
            );
        }
        // Update method stack for future mirrors
        $this->methodStack[] = [
            'method' => $method,
            'args' => $arguments
        ];
        // Update current facets
        $this->updateFacets();
    }

    /**
     * Check if filter would interfere with attribute facet..
     * This looks a bit dirty but is just a fail safe. We are adding facet to pool after applying
     * attribute filter, so this could be removed if wanted.
     *
     * @param $attributeCode
     * @param $method
     * @param $args
     * @return bool
     */
    private function shouldSkip($attributeCode, $method, $args)
    {
        if ($method == 'addAttributeFilter'
            && isset($args[0])
            && $args[0] instanceof Attribute
            && $args[0]->getAttributeCode() == $attributeCode
        ) {
            return true;
        }
        return false;
    }
}
