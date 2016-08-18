<?php
/**
 * @package     BlueAcorn\LayeredNavigation
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\LayeredNavigation\Model\Layer\Filter;

use BlueAcorn\LayeredNavigation\Model\FacetPool;
use Magento\Catalog\Model\Layer\Filter\AbstractFilter;

/**
 * Layer attribute filter
 */
class Attribute extends AbstractFilter
{
    /** @var \Magento\Framework\Filter\StripTags */
    protected $tagFilter;

    /** @var FacetPool */
    protected $facetPool;

    /**
     * @param \Magento\Catalog\Model\Layer\Filter\ItemFactory $filterItemFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Catalog\Model\Layer $layer
     * @param \Magento\Catalog\Model\Layer\Filter\Item\DataBuilder $itemDataBuilder
     * @param \Magento\Framework\Filter\StripTags $tagFilter
     * @param FacetPool $facetPool
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Model\Layer\Filter\ItemFactory $filterItemFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\Layer $layer,
        \Magento\Catalog\Model\Layer\Filter\Item\DataBuilder $itemDataBuilder,
        \Magento\Framework\Filter\StripTags $tagFilter,
        FacetPool $facetPool,
        array $data = []
    ) {
        parent::__construct(
            $filterItemFactory,
            $storeManager,
            $layer,
            $itemDataBuilder,
            $data
        );
        $this->tagFilter = $tagFilter;
        $this->facetPool = $facetPool;
    }

    /**
     * Apply attribute option filter to product collection
     *
     * @param \Magento\Framework\App\RequestInterface $request
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function apply(\Magento\Framework\App\RequestInterface $request)
    {
        $attributeValue = $request->getParam($this->_requestVar);
        if (empty($attributeValue)) {
            return $this;
        }
        /** @var \Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection $productCollection */
        $productCollection = $this->getLayer()->getProductCollection();
        $attribute = $this->getAttributeModel();
        $this->facetPool->addFacet($attribute, $attributeValue);
        // Allow multiple filter values
        $condition = strpos($attributeValue, ',') === false
            ? $attributeValue
            : ['in' => explode(',', $attributeValue)];
        $productCollection->addFieldToFilter($attribute->getAttributeCode(), $condition);
        $label = $this->getOptionText($attributeValue);
        if (is_array($label)) {
            $label = implode(', ', $label);
        }
        $this->getLayer()
            ->getState()
            ->addFilter($this->_createItem($label, $attributeValue));

        return $this;
    }

    /**
     * Get data array for building attribute filter items
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _getItemsData()
    {
        $totalSize = $this->getLayer()->getProductCollection()->getSize();
        $facetedData = $this->_getFacetedData();
        $attribute = $this->getAttributeModel();
        $options = $attribute->getFrontend()->getSelectOptions();
        foreach ($options as $option) {
            if (empty($option['value'])) {
                continue;
            }
            // TODO if value is already applied, check sys config whether or not this should show up as a span?
            if (empty($facetedData[$option['value']])
                || ($this->getAttributeIsFilterable($attribute) == static::ATTRIBUTE_OPTIONS_ONLY_WITH_RESULTS
                    && !$this->isOptionAffectsResults($facetedData[$option['value']], $totalSize))
            ) {
                continue;
            }
            $this->itemDataBuilder->addItemData(
                $this->tagFilter->filter($option['label']),
                $option['value'],
                $facetedData[$option['value']]
            );
        }

        return $this->itemDataBuilder->build();
    }

    /**
     * Get faceted data
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _getFacetedData()
    {
        $attribute = $this->getAttributeModel();
        $facet = $this->facetPool->getFacet($attribute->getAttributeCode());
        if ($facet) {
            return $facet->getFacetedData();
        }

        return array_map(function($data) {
            return $data['count'];
        }, $this->getLayer()->getProductCollection()->getFacetedData($attribute->getAttributeCode()));
    }

    /**
     * Check if option will affect results (change total collection size)
     *
     * @param $optionCount
     * @param $totalSize
     * @return bool
     */
    protected function isOptionAffectsResults($optionCount, $totalSize)
    {
        return $optionCount != $totalSize;
    }
}
