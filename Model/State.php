<?php
/**
 * @package     BlueAcorn\LayeredNavigation
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\LayeredNavigation\Model\Layer;

class State extends \Magento\Catalog\Model\Layer\State
{
    /**
     * Check if state has filter applied
     * (Layer\Filter models are made unique by request var)
     *
     * @param $requestVar
     * @return bool
     */
    public function hasFilter($requestVar)
    {
        foreach ($this->getFilters() as $filterItem) {
            if ($filterItem->getFilter()->getRequestVar() == $requestVar) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get filter by request var
     * (Layer\Filter models are made unique by request var)
     *
     * @param $requestVar
     * @return \Magento\Catalog\Model\Layer\Filter\AbstractFilter|null
     */
    public function getFilter($requestVar)
    {
        foreach ($this->getFilters() as $filterItem) {
            if ($filterItem->getFilter()->getRequestVar() == $requestVar) {
                return $filterItem->getFilter();
            }
        }
        return null;
    }
}
