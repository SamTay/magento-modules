<?php
/**
 * @package     BlueAcorn\LayeredNavigation
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\LayeredNavigation\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

class Price extends AbstractHelper
{
    const XML_PATH_SLIDER_ENABLED = 'catalog/layered_navigation/price_slider_enable';
    const XML_PATH_SLIDER_MIN_RANGE = 'catalog/layered_navigation/price_slider_minimum_range';

    /**
     * Get is slider enabled
     *
     * @return bool
     */
    public function isSliderEnabled()
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_SLIDER_ENABLED);
    }

    /**
     * Get minimum max-min price range to show slider
     *
     * @return int
     */
    public function getSliderMinRange()
    {
        return (int)$this->scopeConfig->getValue(self::XML_PATH_SLIDER_MIN_RANGE) ?: 0;
    }
}
