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
    const XML_PREFIX = 'catalog/layered_navigation/price_slider_';
    const ENABLE = 'enable';
    const NARROW = 'narrow';
    const STEP = 'step';
    const MIN_RANGE = 'minimum_range';

    /**
     * Get is slider enabled
     *
     * @return bool
     */
    public function isSliderEnabled()
    {
        return (bool)$this->getConfigValue(self::ENABLE);
    }

    /**
     * Get slider narrowing setting
     *
     * @return bool
     */
    public function getSliderNarrow()
    {
        return (bool)$this->getConfigValue(self::NARROW);
    }
    /**
     * Get slider step
     *
     * @return float
     */
    public function getSliderStep()
    {
        return (float)$this->getConfigValue(self::STEP) ?: 1.00;
    }

    /**
     * Get minimum max-min price range to show slider
     *
     * @return float
     */
    public function getSliderMinRange()
    {
        return (float)$this->getConfigValue(self::MIN_RANGE) ?: 0;
    }

    /**
     * Get config value
     *
     * @param string $field
     * @return mixed
     */
    protected function getConfigValue($field)
    {
        return $this->scopeConfig->getValue(self::XML_PREFIX . $field);
    }
}
