<?php
/**
 * @package     BlueAcorn\LayeredNavigation
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\LayeredNavigation\Model\ResourceModel\Facet;

use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Layer\Filter\Price as CatalogPriceResource;
use Magento\Framework\DB\Select;

/**
 * Class Price
 * Basically a helper class for the Facet Resource Model -- this class handles low level pricing modifications
 * to select objects.
 * @see \Magento\Catalog\Model\ResourceModel\Layer\Filter\Price
 */
class Price
{
    const TABLE_CATALOG_PRODUCT_INDEX_PRICE = 'catalog_product_index_price';

    /**
     * Add price filter to collection
     *
     * @param ProductCollection $collection
     * @param $from
     * @param $to
     */
    public function addFilter(ProductCollection $collection, $from, $to)
    {
        if ($from === '' && $to === '') {
            return;
        }
        $collection->addPriceData();
        $select = $collection->getSelect();
        if ($to !== '') {
            $to = (double)$to;
            if ($from == $to) {
                $to += CatalogPriceResource::MIN_POSSIBLE_PRICE;
            }
        }
        $priceExpr = $collection->getPriceExpression($select);
        if ($from !== '') {
            $select->where($priceExpr . ' >= ' . $this->_getComparingValue($collection, $from));
        }
        if ($to !== '') {
            $select->where($priceExpr . ' < ' . $this->_getComparingValue($collection, $to));
        }
    }

    /**
     * Get comparing value sql part
     *
     * @param ProductCollection $collection
     * @param float $price
     * @param bool $decrease
     * @return float
     */
    protected function _getComparingValue(ProductCollection $collection, $price, $decrease = true)
    {
        $currencyRate = $collection->getCurrencyRate();
        if ($decrease) {
            return ($price - CatalogPriceResource::MIN_POSSIBLE_PRICE / 2) / $currencyRate;
        }
        return ($price + CatalogPriceResource::MIN_POSSIBLE_PRICE / 2) / $currencyRate;
    }
}
