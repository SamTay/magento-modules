<?php
/**
 * @package     BlueAcorn\LayeredNavigation
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\LayeredNavigation\Model\Layer\Filter;

use BlueAcorn\LayeredNavigation\Helper\Config as ConfigHelper;

/**
 * Override from native to hook into collection mirror.
 * Use price slider if enabled
 */
class Price extends \Magento\CatalogSearch\Model\Layer\Filter\Price
{
    /** @var ConfigHelper */
    protected $helper;

    /** @var \BlueAcorn\LayeredNavigation\Model\Layer\CollectionMirror\Proxy */
    protected $collectionMirror;

    /**
     * @param \Magento\Catalog\Model\Layer\Filter\ItemFactory $filterItemFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Catalog\Model\Layer $layer
     * @param \Magento\Catalog\Model\Layer\Filter\Item\DataBuilder $itemDataBuilder
     * @param \Magento\Catalog\Model\ResourceModel\Layer\Filter\Price $resource
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\Search\Dynamic\Algorithm $priceAlgorithm
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param \Magento\Catalog\Model\Layer\Filter\Dynamic\AlgorithmFactory $algorithmFactory
     * @param \Magento\Catalog\Model\Layer\Filter\DataProvider\PriceFactory $dataProviderFactory
     * @param ConfigHelper $helper
     * @param \BlueAcorn\LayeredNavigation\Model\Layer\CollectionMirror\Proxy $collectionMirror
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __construct(
        \Magento\Catalog\Model\Layer\Filter\ItemFactory $filterItemFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\Layer $layer,
        \Magento\Catalog\Model\Layer\Filter\Item\DataBuilder $itemDataBuilder,
        \Magento\Catalog\Model\ResourceModel\Layer\Filter\Price $resource,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Search\Dynamic\Algorithm $priceAlgorithm,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Catalog\Model\Layer\Filter\Dynamic\AlgorithmFactory $algorithmFactory,
        \Magento\Catalog\Model\Layer\Filter\DataProvider\PriceFactory $dataProviderFactory,
        ConfigHelper $helper,
        \BlueAcorn\LayeredNavigation\Model\Layer\CollectionMirror\Proxy $collectionMirror,
        array $data = []
    ) {
        parent::__construct(
            $filterItemFactory,
            $storeManager,
            $layer,
            $itemDataBuilder,
            $resource,
            $customerSession,
            $priceAlgorithm,
            $priceCurrency,
            $algorithmFactory,
            $dataProviderFactory,
            $data
        );
        $this->helper = $helper;
        $this->collectionMirror = $collectionMirror;
    }

    /**
     * Apply price range filter
     *
     * @param \Magento\Framework\App\RequestInterface $request
     * @return $this
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function apply(\Magento\Framework\App\RequestInterface $request)
    {
        parent::apply($request);

        // If price filter has been applied, forward filter to facet pool
        $appliedFilter = $this->getLayer()->getState()->getItemByFilter($this);
        if ($appliedFilter) {
            list($from, $to) = $appliedFilter->getValue();
            $this->collectionMirror->addPriceFilter($from, $to);
        }
        return $this;
    }

    /**
     * Get slider data (single filter item with min,max info)
     * Sticking slider within normal filter, filter items data so that it can leverage sorting etc.
     *
     * @return array
     */
    protected function _getSliderData()
    {
        /** @var \Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection $productCollection */
        $productCollection = $this->getLayer()->getProductCollection();
        $applied = $this->getLayer()->getState()->getItemByFilter($this);
        // If we should be narrowing the results, use min/max of current layer
        if ($this->helper->getSliderNarrow() || !$applied) {
            $min = $productCollection->getMinPrice() ?: 0;
            $max = $productCollection->getMaxPrice() ?: $min;
            $current = [$min, $max];
            $count = $productCollection->getSize();
        // Otherwise use min/max of current layer outside of price filter
        } else {
            $facetedData = $this->collectionMirror->getPricingData();
            $min = (double)$facetedData['min'];
            $max = (double)$facetedData['max'];
            $current = $applied->getValue();
            $count = (int)$facetedData['count'];
        }
        $minimumRange = $this->helper->getSliderMinRange();
        if (abs($max - $min) < $minimumRange || abs($max - $min) == 0) {
            return [];
        }
        return [[
            'label' => __('Price'),
            'min' => $min,
            'max' => $max,
            'current' => $current,
            'count' => $count // This is probably not necessary...
        ]];
    }

    /**
     * Override to include extra slider data on filter item
     *
     * @return $this|\Magento\Catalog\Model\Layer\Filter\AbstractFilter
     */
    protected function _initItems()
    {
        if (!$this->helper->isSliderEnabled()) {
            return parent::_initItems();
        }

        $data = $this->_getSliderData();
        $items = [];
        foreach ($data as $itemData) {
            $items[] = $this->_filterItemFactory->create()
                ->setData($itemData);
        }
        $this->_items = $items;
        return $this;
    }
}
