<?php
/**
 * @package     BlueAcorn\LayeredNavigation
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\LayeredNavigation\Block\Navigation;

use Magento\Catalog\Model\Layer\Filter\Item;
use Magento\LayeredNavigation\Block\Navigation\FilterRenderer;

class CheckboxRenderer extends FilterRenderer
{
    /**
     * Get URL for checkbox onclick
     *
     * @param Item $filterItem
     * @return string
     */
    public function getFilterUrl(Item $filterItem)
    {
        $filterUrl = $filterItem->getAlreadyApplied()
            ? $this->getRemoveFilterUrl($filterItem)
            : $filterItem->getUrl();

        return $this->escapeUrl($filterUrl);
    }

    /**
     * Get URL for removing one filter option
     *
     * @param Item $filterItem
     * @return string
     */
    public function getRemoveFilterUrl(Item $filterItem)
    {
        $appliedFilterValues = $this->getAppliedValues($filterItem);
        $filterValue = $filterItem->getLoneValue() ?: $filterItem->getValue();
        if (strpos($filterValue, ',') !== false) {
            $filterValue = substr($filterValue, strrpos($filterValue, ','));
        }
        $removalFilterValues = array_diff($appliedFilterValues, [$filterValue]);
        $removalQueryValue = $removalFilterValues ? implode(',', $removalFilterValues) : null;
        $query = [$filterItem->getFilter()->getRequestVar() => $removalQueryValue];
        $params['_current'] = true;
        $params['_use_rewrite'] = true;
        $params['_escape'] = true;
        $params['_query'] = $query;
        return $this->_urlBuilder->getUrl('*/*/*', $params);
    }

    /**
     * Get current state applied values for associated filter
     *
     * @param Item $filterItem
     * @return array
     */
    protected function getAppliedValues(Item $filterItem)
    {
        $state = $filterItem->getFilter()->getLayer()->getState();
        $appliedItem = $state->getItemByFilter($filterItem->getFilter());
        return $appliedItem
            ? explode(',', $appliedItem->getValue())
            : [];
    }
}
