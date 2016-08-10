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
        $condition = strpos($attributeValue, ',') === false
            ? $attributeValue
            : ['in' => explode(',', $attributeValue)];
        $attribute = $this->getAttributeModel();
        $productCollection->addFieldToFilter($attribute->getAttributeCode(), $condition);
        $label = $this->getOptionText($attributeValue);
        if (is_array($label)) {
            $label = implode(', ', $label);
        }
        $this->getLayer()
            ->getState()
            ->addFilter($this->_createItem($label, $attributeValue));

        //$this->setItems([]); // set items to disable show filtering
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
        $attribute = $this->getAttributeModel();
        /** @var \Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection $productCollection */
        $productCollection = $this->facetPool->getFacet($attribute->getAttributeCode());
        // TODO see if getting faceted data PER collection is viable, or if we should just execute some SQL
        // on the collections in facet pool
        $optionsFacetedData = $productCollection->getFacetedData($attribute->getAttributeCode());

        $productSize = $productCollection->getSize();

        $options = $attribute->getFrontend()
            ->getSelectOptions();
        foreach ($options as $option) {
            if (empty($option['value'])) {
                continue;
            }
            // Check filter type
            if (empty($optionsFacetedData[$option['value']]['count'])
                || ($this->getAttributeIsFilterable($attribute) == static::ATTRIBUTE_OPTIONS_ONLY_WITH_RESULTS
                    && !$this->isOptionReducesResults($optionsFacetedData[$option['value']]['count'], $productSize))
            ) {
                continue;
            }
            $this->itemDataBuilder->addItemData(
                $this->tagFilter->filter($option['label']),
                $option['value'],
                $optionsFacetedData[$option['value']]['count']
            );
        }

        return $this->itemDataBuilder->build();
    }
}
