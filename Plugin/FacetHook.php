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

/**
 * TODO: Decide whether this should remain a plugin (global for any fulltext collection)
 * or just inject a custom wrapper object into the collection provider (hooks only for single collection belonging to layer)
 */
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
     * Add facet for this filter
     *
     * @param FulltextCollection $subject
     * @param $field
     * @param null $condition
     */
    public function beforeAddFieldToFilter(FulltextCollection $subject, $field, $condition = null)
    {
        $this->facetPool->addFacet($field, $condition);
        return;
    }
}
