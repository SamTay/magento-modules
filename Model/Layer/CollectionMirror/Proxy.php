<?php
/**
 * @package     BlueAcorn\LayeredNavigation
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\LayeredNavigation\Model\Layer\CollectionMirror;

use BlueAcorn\LayeredNavigation\Model\Layer\CollectionMirror;
use BlueAcorn\LayeredNavigation\Model\Layer\CollectionMirrorFactory;

/**
 * Class Proxy
 * @method addAttributeFilter(Attribute $attribute, string $attributeValue)
 * @method addDecimalFilter(Attribute $attribute, $from, $to)
 * @method addPriceFilter($from, $to)
 * @method array getFacetedData(Attribute $attribute, string $attributeValue)
 * @method array getPricingData()
 */
class Proxy
{
    /** @var CollectionMirror */
    private $collectionMirror = null;

    /** @var CollectionMirrorFactory */
    private $collectionMirrorFactory;

    /** @var array pairs of ['method' => 'name', 'args' => []] */
    private $methodStack = [];

    /** @var array methods that should cause a 'load' effect */
    private $methodsToLoad = ['getFacetedData', 'getPricingData'];

    /**
     * CollectionMirror constructor.
     * @param CollectionMirrorFactory $collectionMirrorFactory
     */
    public function __construct(
        CollectionMirrorFactory $collectionMirrorFactory
    ) {
        $this->collectionMirrorFactory = $collectionMirrorFactory;
    }

    /**
     * Magic caller that proxies to collection mirror
     *
     * @param $method
     * @param $args
     * @return $this|mixed
     */
    public function __call($method, $args)
    {
        if (in_array($method, $this->methodsToLoad) || !is_null($this->collectionMirror)) {
            $this->initialize();
            return call_user_func_array([$this->collectionMirror, $method], $args);
        }

        $this->addToStack($method, $args);
        return $this;
    }

    /**
     * Add method & args to a hypothetical stack
     * These methods will only be called if collection instantiation is necessary
     *
     * @param $method
     * @param array $args
     */
    protected function addToStack($method, $args = [])
    {
        $this->methodStack[] = [
            'method' => $method,
            'args' => $args
        ];
    }

    /**
     * Initialize collection property, apply all methods from method stack
     */
    protected function initialize()
    {
        if (is_null($this->collectionMirror)) {
            $this->collectionMirror = $this->collectionMirrorFactory->create();
            while ($this->methodStack) {
                $func = array_shift($this->methodStack);
                call_user_func_array([$this->collectionMirror, $func['method']], $func['args']);
            }
        }
    }
}
