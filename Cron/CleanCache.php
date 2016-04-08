<?php
/**
 * @package     BlueAcorn\ContentPublisher
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\ContentPublisher\Cron;

use BlueAcorn\ContentPublisher\Helper\Publisher;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Cms\Model\ResourceModel\Page\CollectionFactory as PageCollectionFactory;
use Magento\Cms\Model\ResourceModel\Page\Collection as PageCollection;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

/**
 * Class CleanCache
 * Cron to set publishing status on products & cms pages
 *
 * Note: appears impossible to handle nested or->and filters with SearchCriteriaInterface,
 * so I can't filter by publish_start in refresh interval OR publish_end in refresh interval,
 * which each require AND [from,to] condition types. Hence, using product collection instead of repository
 */
class CleanCache
{
    const REFRESH_RATE_MINUTES = 5;
    const REFRESH_BUFFER_SECONDS = 30;
    const DATE_FORMAT = 'Y-m-d H:i:s';

    /**
     * @var PageCollectionFactory
     */
    protected $_pageCollectionFactory;

    /**
     * @var ProductCollectionFactory
     */
    protected $_productCollectionFactory;

    /**
     * @var TimezoneInterface
     */
    protected $_localeDate;

    /**
     * @param PageCollectionFactory $pageCollectionFactory
     * @param ProductCollectionFactory $productCollectionFactory
     * @param TimezoneInterface $localeDate
     */
    public function __construct(
        PageCollectionFactory $pageCollectionFactory,
        ProductCollectionFactory $productCollectionFactory,
        TimezoneInterface $localeDate
    ) {
        $this->_pageCollectionFactory = $pageCollectionFactory;
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->_localeDate = $localeDate;
    }

    /**
     * Cronjob to publish or disable entities
     */
    public function execute()
    {
        foreach (['_page', '_product'] as $entityPrefix) {
            /** @var PageCollection|BlockCollection $collection */
            $collection = $this->{$entityPrefix . 'CollectionFactory'}->create();
            $this->_filterRecent($collection);
            foreach($collection as $entity) {
                // Status will be handled on _before_save events
                // Full save will ensure hooks into status updates are executed and that cache is cleaned
                /** @var \Magento\Framework\Model\AbstractModel $entity */
                $entity->load($entity->getId())->save();
            }
        }
    }

    /**
     * Filters collection to only include entities whose publish start OR end dates occur
     * between the last cron job and right now
     *
     * @param PageCollection|ProductCollection $collection
     * @return PageCollection|ProductCollection
     */
    protected function _filterRecent($collection)
    {
        $now = $this->_getNow();
        $lastRefresh = $this->_getLastRefresh();
        // M2 seems to not handle the addFieldToFilter compatibility very well
        if ($collection instanceof \Magento\Eav\Model\Entity\Collection\AbstractCollection) {
            $collection
                ->addAttributeToFilter(
                    ['attribute' => 'publish_start', 'date' => true, 'from' => $lastRefresh, 'to' => $now]
                )->addAttributeToFilter(
                    ['attribute' => 'publish_end', 'date' => true, 'from' => $lastRefresh, 'to' => $now]
                );
        } else {
            $collection->addFieldToFilter(
                ['publish_start', 'publish_end'],
                [['from' => $lastRefresh, 'to' => $now], ['from' => $lastRefresh, 'to' => $now]]
            );
        }
    }

    /**
     * Datetime getter
     *
     * @return string
     */
    protected function _getNow()
    {
        return $this->_localeDate->date()->format(self::DATE_FORMAT);
    }

    /**
     * Get last refresh time with buffer, just for an edge case where an publish_start/end
     * date is right on the edge of cron refreshes. Because cronjobs aren't instantaneous, this very
     * small edge case exists.
     * TODO: grab current cron schedule instead of self::REFRESH_RATE_MINUTES constant
     * @return string
     */
    protected function _getLastRefresh()
    {
        $refreshInterval = new \DateInterval('PT'
            . self::REFRESH_RATE_MINUTES
            . 'M'
            . self::REFRESH_BUFFER_SECONDS
            . 'S'
        );
        return $this->_localeDate->date()->sub($refreshInterval)->format(self::DATE_FORMAT);
    }
}
