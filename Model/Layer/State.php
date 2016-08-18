<?php
/**
 * @package     BlueAcorn\LayeredNavigation
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\LayeredNavigation\Model\Layer;

use Magento\Catalog\Model\Layer\Filter\AbstractFilter;

class State extends \Magento\Catalog\Model\Layer\State
{
    /**
     * Check if state has filter applied
     * (Layer\Filter models are made unique by request var)
     *
     * @param AbstractFilter $filter
     * @return bool
     */
    public function hasFilter(AbstractFilter $filter)
    {
        foreach ($this->getFilters() as $filterItem) {
            if ($filterItem->getFilter()->getRequestVar() == $filter->getRequestVar()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get filter item by filter
     * Warning:: Assuming that there is only one filter item per filter
     *  - if this is incorrect, refactor this!
     *
     * @param AbstractFilter $filter
     * @return \Magento\Catalog\Model\Layer\Filter\Item
     */
    public function getItemByFilter(AbstractFilter $filter)
    {
        foreach ($this->getFilters() as $filterItem) {
            if ($filterItem->getFilter()->getRequestVar() == $filter->getRequestVar()) {
                return $filterItem;
            }
        }
        return null;
    }
}
