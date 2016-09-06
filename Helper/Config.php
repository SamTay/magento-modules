<?php
/**
 * @package     BlueAcorn\LayeredNavigation
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\LayeredNavigation\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

class Config extends AbstractHelper
{
    const SLIDER_XML_PREFIX = 'catalog/layered_navigation/price_slider_';
    const SLIDER_ENABLE = 'enable';
    const SLIDER_NARROW = 'narrow';
    const SLIDER_STEP = 'step';
    const SLIDER_MIN_RANGE = 'minimum_range';

    const MULTIVALUE_XML_PREFIX = 'catalog/layered_navigation/multivalue_filter_';
    const MULTIVALUE_ENABLE = 'enable';

    const DEPENDENCY_JOIN_XML = 'catalog/layered_navigation/dependency_join';

    /**
     * Get logical joining for multiple dependencies
     *
     * @return mixed
     */
    public function getDependencyJoin()
    {
        return $this->scopeConfig->getValue(self::DEPENDENCY_JOIN_XML);
    }

    /**
     * Get is multi value filtering enabled
     *
     * @return mixed
     */
    public function isMultiValueEnabled()
    {
        return $this->getMultiValueConfig(self::MULTIVALUE_ENABLE);
    }

    /**
     * Get is slider enabled
     *
     * @return bool
     */
    public function isSliderEnabled()
    {
        return (bool)$this->getSliderConfig(self::SLIDER_ENABLE);
    }

    /**
     * Get slider narrowing setting
     *
     * @return bool
     */
    public function getSliderNarrow()
    {
        return (bool)$this->getSliderConfig(self::SLIDER_NARROW);
    }
    /**
     * Get slider step
     *
     * @return float
     */
    public function getSliderStep()
    {
        return (float)$this->getSliderConfig(self::SLIDER_STEP) ?: 1.00;
    }

    /**
     * Get minimum max-min price range to show slider
     *
     * @return float
     */
    public function getSliderMinRange()
    {
        return (float)$this->getSliderConfig(self::SLIDER_MIN_RANGE) ?: 0;
    }

    /**
     * Get multivalue config value
     *
     * @param string $field
     * @return mixed
     */
    public function getMultiValueConfig($field)
    {
        return $this->scopeConfig->getValue(self::MULTIVALUE_XML_PREFIX . $field);
    }

    /**
     * Get config value
     *
     * @param string $field
     * @return mixed
     */
    public function getSliderConfig($field)
    {
        return $this->scopeConfig->getValue(self::SLIDER_XML_PREFIX . $field);
    }
}
