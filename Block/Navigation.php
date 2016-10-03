<?php
/**
 * @package     BlueAcorn\LayeredNavigation
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\LayeredNavigation\Block;

use BlueAcorn\LayeredNavigation\Api\DependencyManagerInterface;
use Magento\Catalog\Model\Layer\Filter\FilterInterface;
use Magento\Catalog\Model\Layer\FilterList;
use BlueAcorn\LayeredNavigation\Helper\Config as ConfigHelper;

class Navigation extends \Magento\LayeredNavigation\Block\Navigation
{
    const DEFAULT_ALIAS = 'renderer.default';
    const CHECKBOX_ALIAS = 'renderer.checkbox';
    const SLIDER_ALIAS = 'renderer.slider';

    /** @var ConfigHelper */
    protected $configHelper;

    /** @var DependencyManagerInterface */
    protected $dependencyManager;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Catalog\Model\Layer\Resolver $layerResolver
     * @param \Magento\Catalog\Model\Layer\FilterList $filterList
     * @param \Magento\Catalog\Model\Layer\AvailabilityFlagInterface $visibilityFlag
     * @param ConfigHelper $configHelper
     * @param DependencyManagerInterface $dependencyManager
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Catalog\Model\Layer\Resolver $layerResolver,
        \Magento\Catalog\Model\Layer\FilterList $filterList,
        \Magento\Catalog\Model\Layer\AvailabilityFlagInterface $visibilityFlag,
        ConfigHelper $configHelper,
        DependencyManagerInterface $dependencyManager,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $layerResolver,
            $filterList,
            $visibilityFlag,
            $data
        );
        $this->configHelper = $configHelper;
        $this->dependencyManager = $dependencyManager;
    }

    /**
     * Get filters but remove the ones with unmet dependencies
     *
     * @return array
     */
    public function getApplicableFilters()
    {
        $unmetDependencies = $this->dependencyManager->getUnmetDependencies($this->_catalogLayer);
        return array_filter($this->getFilters(), function($filter) use($unmetDependencies) {
            $attribute = $filter->getData('attribute_model');
            return !$attribute || !in_array($attribute->getId(), $unmetDependencies);
        });
    }

    /**
     * @param FilterInterface $filter
     * @return string
     */
    public function renderFilter(FilterInterface $filter)
    {
        $alias = self::DEFAULT_ALIAS;
        $filterType = $this->resolveType($filter);
        if ($filterType == FilterList::PRICE_FILTER
            && $this->configHelper->isSliderEnabled()
        ) {
            $alias = self::SLIDER_ALIAS;
        }
        if ($filterType != FilterList::PRICE_FILTER
            && $this->configHelper->getCheckboxTheme()
        ) {
            $alias = self::CHECKBOX_ALIAS;
        }

        return $this->getChildBlock($alias)->render($filter);
    }

    /**
     * @see FilterList::getAttributeFilterClass
     * @param FilterInterface $filter
     * @return string
     */
    private function resolveType(FilterInterface $filter)
    {
        // First check if category so we don't raise exceptions on `getAttributeModel`
        if ($filter->getRequestVar() == 'cat') {
            return FilterList::CATEGORY_FILTER;
        }
        $attribute = $filter->getAttributeModel();
        if ($attribute->getAttributeCode() == 'price') {
            return FilterList::PRICE_FILTER;
        } elseif ($attribute->getBackendType() == 'decimal') {
            return FilterList::DECIMAL_FILTER;
        }

        // Default to attribute filter
        return FilterList::ATTRIBUTE_FILTER;
    }
}
