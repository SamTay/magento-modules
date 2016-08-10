<?php
/**
 * @package     BlueAcorn\LayeredNavigation
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\LayeredNavigation\Plugin;

use BlueAcorn\LayeredNavigation\Model\FacetPool;
use Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection as FulltextCollection;

class FacetHook
{
    /** @var FacetPool */
    protected $facetPool;

    /**
     * FacetHook constructor.
     * @param FacetPool $facetPool
     */
    public function __construct(
        FacetPool $facetPool
    ) {
        $this->facetPool = $facetPool;
    }

    /**
     * TODO: Right now this plugin will apply to all pooled facet collections :( no bueno,
     * maybe extend dummy class to avoid plugin
     *
     * @param FulltextCollection $subject
     * @param \Closure $proceed
     * @param $field
     * @param null $condition
     * @param return FulltextCollection
     */
    public function aroundAddFieldToFilter(FulltextCollection $subject, \Closure $proceed, $field, $condition = null)
    {
        // TODO maybe check if $field is a non price/decimal attribute ?? Not sure ...
        // if all price/decimals are using sliders then we shouldn't worry about faceting against them
        $this->facetPool->addFacet($field, $subject);
        $return = $proceed($field, $condition);
        $this->facetPool->addFieldToFilter($field, $condition);
        return $return;
    }
}
