<?php
/**
 * @package     BlueAcorn\LayeredNavigation
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\LayeredNavigation\Model\Layer\Filter;

use Magento\Catalog\Model\Layer\Filter\AbstractFilter;

/**
 * Layer attribute filter
 */
class Attribute extends AbstractFilter
{
    /** @var \Magento\Framework\Filter\StripTags */
    protected $tagFilter;

    /** @var \BlueAcorn\LayeredNavigation\Model\Layer\CollectionMirror */
    protected $collectionMirror;

    /**
     * @param \Magento\Catalog\Model\Layer\Filter\ItemFactory $filterItemFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Catalog\Model\Layer $layer
     * @param \Magento\Catalog\Model\Layer\Filter\Item\DataBuilder $itemDataBuilder
     * @param \Magento\Framework\Filter\StripTags $tagFilter
     * @param \BlueAcorn\LayeredNavigation\Model\Layer\CollectionMirror $collectionMirror
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Model\Layer\Filter\ItemFactory $filterItemFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\Layer $layer,
        \Magento\Catalog\Model\Layer\Filter\Item\DataBuilder $itemDataBuilder,
        \Magento\Framework\Filter\StripTags $tagFilter,
        \BlueAcorn\LayeredNavigation\Model\Layer\CollectionMirror $collectionMirror,
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
        $this->collectionMirror = $collectionMirror;
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
        $this->collectionMirror->addAttributeFilter($attribute, $attributeValue);
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
     * If a filter has been applied for this attribute,
     * -- use mirror collection (handles OR logic for multi value filtering)
     * else
     * -- use default fulltext aggregation data
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _getFacetedData()
    {
        $attribute = $this->getAttributeModel();
        $appliedFilter = $this->getLayer()->getState()->getItemByFilter($this);
        if ($appliedFilter) {
            return $this->collectionMirror->getFacetedData($attribute, $appliedFilter->getValue());
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
