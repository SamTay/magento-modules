<?php
/**
 * @package     BlueAcorn\ContentPublisher
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\ContentPublisher\Observer\Adminhtml\Product;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

/**
 * Class SetStartDateMaxValue
 * Observes catalog_product_validate_before
 * Purpose: Set max value on start date equal to end date value
 */
class SetStartDateMaxValue implements ObserverInterface
{
    /**
     * Execute observer
     * @param EventObserver $observer
     */
    public function execute(EventObserver $observer)
    {
        /** @var \Magento\Catalog\Model\Product $product */
        $product = $observer->getEvent()->getProduct();
        $product->getResource()->getAttribute('publish_start')
            ->setMaxValue($product->getPublishEnd());
    }
}
