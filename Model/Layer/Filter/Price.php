<?php
/**
 * @package     BlueAcorn\LayeredNavigation
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\LayeredNavigation\Model\Layer\Filter;

use BlueAcorn\LayeredNavigation\Helper\Price as PriceHelper;

/**
 * Override from native to hook into collection mirror.
 * Use price slider if enabled
 */
class Price extends \Magento\CatalogSearch\Model\Layer\Filter\Price
{
    /** @var PriceHelper */
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
     * @param PriceHelper $helper
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
        PriceHelper $helper,
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
     * Get data array for building attribute filter items
     *
     * @return array
     */
    protected function _getItemsData()
    {
        if (!$this->helper->isSliderEnabled()) {
            return parent::_getItemsData();
        }
        return $this->getSliderData();
    }

    /**
     * @return array
     */
    protected function getSliderData()
    {
        /** @var \Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection $productCollection */
        $productCollection = $this->getLayer()->getProductCollection();
        $min = $productCollection->getMinPrice();
        $max = $productCollection->getMaxPrice();
        $minimumRange = $this->helper->getSliderMinRange();
        return []; // todo finish implementing
    }
}
