<?php
/**
 * @package     BlueAcorn\ContentPublisher
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\ContentPublisher\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use BlueAcorn\ContentPublisher\Helper\Publisher;
use Magento\Cms\Model\Page;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status as ProductStatus;
use Magento\Framework\Model\AbstractModel;

/**
 * Class CheckPublishDates
 * Observes {entity}_save_before events
 * Purpose: Switch `status` values based on publish dates
 */
class CheckPublishDates implements ObserverInterface
{
    /**
     * @var Publisher
     */
    protected $_publisher;

    /**
     * @param Publisher $publisher
     */
    public function __construct(Publisher $publisher)
    {
        $this->_publisher = $publisher;
    }

    /**
     * @param EventObserver $observer
     */
    public function execute(EventObserver $observer)
    {
        /** @var Product|Page $entity */
        $entity = $observer->getEvent()->getDataObject();
        $status = $this->_publisher->getStatus($entity);
        switch($status) {
            case Publisher::STATUS_PUBLISH:
                $this->_publish($entity);
                break;
            case Publisher::STATUS_DISABLE:
                $this->_disable($entity);
                break;
            case Publisher::STATUS_IGNORE:
                // Explicitly doing nothing
                break;
        }
    }

    /**
     * Enable the model argument
     * Currently handles products and cms pages
     *
     * @param AbstractModel $entity
     */
    protected function _publish(AbstractModel $entity)
    {
        switch(true) {
            case ($entity instanceof Product):
                $entity->setStatus(ProductStatus::STATUS_ENABLED);
                break;
            case ($entity instanceof Page):
                $entity->setIsActive(Page::STATUS_ENABLED);
                break;
        }
    }

    /**
     * Disable the model argument
     * Currently handles products and cms pages
     *
     * @param AbstractModel $entity
     */
    protected function _disable(AbstractModel $entity)
    {
        switch(true) {
            case ($entity instanceof Product):
                $entity->setStatus(ProductStatus::STATUS_DISABLED);
                break;
            case ($entity instanceof Page):
                $entity->setIsActive(Page::STATUS_DISABLED);
                break;
        }
    }
}
