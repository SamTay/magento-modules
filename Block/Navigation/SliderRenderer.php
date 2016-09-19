<?php
/**
 * @package     BlueAcorn\LayeredNavigation
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\LayeredNavigation\Block\Navigation;

use BlueAcorn\LayeredNavigation\Helper\Config;
use Magento\Catalog\Model\Layer\Filter\FilterInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\Template;
use Magento\LayeredNavigation\Block\Navigation\FilterRendererInterface;

class SliderRenderer extends Template implements FilterRendererInterface
{
    /** @var PriceCurrencyInterface */
    protected $currency;

    /** @var Config */
    protected $config;

    /**
     * SliderRenderer constructor.
     * @param Template\Context $context
     * @param PriceCurrencyInterface $currency
     * @param Config $config
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        PriceCurrencyInterface $currency,
        Config $config,
        array $data
    ) {
        parent::__construct($context, $data);
        $this->currency = $currency;
        $this->config = $config;
    }

    /**
     * Get current url without price filter parameter
     *
     * @return string
     */
    public function getFilterBaseUrl()
    {
        $currentUrl = $this->getUrl('*/*/*', ['_current' => true]);
        if (strpos($currentUrl, '?') !== false) {
            list($base, $queryString) = explode('?', $currentUrl);
            parse_str($queryString, $arr);
            unset($arr['price']); // remove the price parameter
            $currentUrl = (empty($arr))
                ? $base
                : $base . '?' . http_build_query($arr);
        }
        return $currentUrl;
    }

    /**
     * @param FilterInterface $filter
     * @return string
     */
    public function render(FilterInterface $filter)
    {
        if ($filter->getItemsCount() != 1) {
            throw new \BadMethodCallException('The slider renderer should only render a single filter item');
        }
        $items = $filter->getItems();
        $sliderItem = reset($items);
        $this->assign($sliderItem->getData());
        $this->assign('symbol', $this->currency->getCurrencySymbol());
        $this->assign('step', $this->config->getSliderStep());
        $html = $this->_toHtml();
        $this->_viewVars = [];
        return $html;
    }
}
