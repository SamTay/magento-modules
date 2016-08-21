<?php
/**
 * @package     BlueAcorn\LayeredNavigation
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\LayeredNavigation\Model\Layer\Filter;

/**
 * Override from native to hook into collection mirror.
 * Right now, restricting multi value sorting to select/multiselect attributes
 *
 * TODO: Override _getItemsData or rendering logic to put a range slider on the frontend for all decimal filters
 */
class Decimal extends \Magento\CatalogSearch\Model\Layer\Filter\Decimal
{
    /** @var \BlueAcorn\LayeredNavigation\Model\Layer\CollectionMirror\Proxy */
    protected $collectionMirror;

    /**
     * @param \Magento\Catalog\Model\Layer\Filter\ItemFactory $filterItemFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Catalog\Model\Layer $layer
     * @param \Magento\Catalog\Model\Layer\Filter\Item\DataBuilder $itemDataBuilder
     * @param \Magento\Catalog\Model\ResourceModel\Layer\Filter\DecimalFactory $filterDecimalFactory
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param \BlueAcorn\LayeredNavigation\Model\Layer\CollectionMirror\Proxy $collectionMirror
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Model\Layer\Filter\ItemFactory $filterItemFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\Layer $layer,
        \Magento\Catalog\Model\Layer\Filter\Item\DataBuilder $itemDataBuilder,
        \Magento\Catalog\Model\ResourceModel\Layer\Filter\DecimalFactory $filterDecimalFactory,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \BlueAcorn\LayeredNavigation\Model\Layer\CollectionMirror\Proxy $collectionMirror,
        array $data = []
    ) {
        parent::__construct(
            $filterItemFactory,
            $storeManager,
            $layer,
            $itemDataBuilder,
            $filterDecimalFactory,
            $priceCurrency,
            $data
        );
        $this->collectionMirror = $collectionMirror;
    }

    /**
     * Apply price range filter
     *
     * @param \Magento\Framework\App\RequestInterface $request
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function apply(\Magento\Framework\App\RequestInterface $request)
    {
        parent::apply($request);

        /** No new facets for decimal filter, just need filter applied to current facet pool */
        if ($this->getLayer()->getState()->hasFilter($this)) {
            $filter = $request->getParam($this->getRequestVar()) ?: '0-*'; // just in case
            list($from, $to) = explode('-', $filter);
            $this->collectionMirror->addDecimalFilter($this->getAttributeModel(), $from, $to);
        }

        return $this;
    }
}
