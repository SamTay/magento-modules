<?php
/**
 * @package     BlueAcorn\ContentScheduler
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\ContentScheduler\Cron;

use Magento\Cms\Model\Page;
use Magento\Cms\Model\Block;
use Magento\Cms\Model\ResourceModel\Page\CollectionFactory as PageCollectionFactory;
use Magento\Cms\Model\ResourceModel\Block\CollectionFactory as BlockCollectionFactory;
use Magento\Cms\Model\PageFactory;
use Magento\Cms\Model\BlockFactory;
use Magento\Cms\Model\ResourceModel\Page\Collection as PageCollection;
use Magento\Cms\Model\ResourceModel\Block\Collection as BlockCollection;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\Event\ManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class CleanCmsCache
 * Cron job to granularly clean cms cache (per affected objects)
 */
class CleanCmsCache
{
    const REFRESH_RATE_MINUTES = 5;
    const REFRESH_BUFFER_SECONDS = 55;
    const DATE_FORMAT = 'Y-m-d H:i:s';

    /**
     * @var PageCollectionFactory
     */
    protected $_pageCollectionFactory;

    /**
     * @var BlockCollectionFactory
     */
    protected $_blockCollectionFactory;

    /**
     * @var TimezoneInterface
     */
    protected $_localeDate;

    /**
     * @var ManagerInterface
     */
    protected $_eventManager;

    /**
     * @var PageFactory
     */
    protected $_pageFactory;

    /**
     * @var BlockFactory
     */
    protected $_blockFactory;

    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * Flag to start debugging or not
     * @var bool
     */
    protected $_debugMode = false;

    /**
     * InvalidatePageCache constructor.
     * @param PageCollectionFactory $pageCollectionFactory
     * @param BlockCollectionFactory $blockCollectionFactory
     * @param TimezoneInterface $localeDate
     * @param ManagerInterface $manager
     * @param PageFactory $pageFactory
     * @param BlockFactory $blockFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        PageCollectionFactory $pageCollectionFactory,
        BlockCollectionFactory $blockCollectionFactory,
        TimezoneInterface $localeDate,
        ManagerInterface $manager,
        PageFactory $pageFactory,
        BlockFactory $blockFactory,
        LoggerInterface $logger

    ) {
        $this->_pageCollectionFactory = $pageCollectionFactory;
        $this->_blockCollectionFactory = $blockCollectionFactory;
        $this->_localeDate = $localeDate;
        $this->_eventManager = $manager;
        $this->_pageFactory = $pageFactory;
        $this->_blockFactory = $blockFactory;
        $this->_logger = $logger;
    }

    /**
     * Cronjob to invalidate cms page/blocks if alternate content needs to be swapped or unswapped
     */
    public function execute()
    {
        $this->_debug('Starting cache cleaning for scheduler...');
        $invalidCacheEntities = [];
        foreach (['_page', '_block'] as $entityPrefix) {
            /** @var PageCollection|BlockCollection $collection */
            $collection = $this->{$entityPrefix . 'CollectionFactory'}->create();
            $this->_filterRecentAlternates($collection);
            foreach($collection as $entity) {
                /** @var Page|Block $entity */
                $invalidCacheEntities[] = $entity;
                // If content is expiring, flush cache on the alternate object as well
                if ($entity->getAlternateEnd() <= $this->_getNow()) {
                    $alternateId = $entity->getAlternate();
                    $alternateEntity = $this->{$entityPrefix . 'Factory'}->create()->load($alternateId);
                    if ($alternateEntity->getId()) {
                        $invalidCacheEntities[] = $alternateEntity;
                    }
                }
            }
        }

        foreach($invalidCacheEntities as $entity) {
            $this->_debug(__("Cleaning cache for %1 with id=%2", $entity::CACHE_TAG, $entity->getId()));
            $this->_eventManager->dispatch('clean_cache_by_tags', ['object' => $entity]);
        }

        $this->_debug('Done cache cleaning for scheduler');
    }

    /**
     * Filters collection to only include entities whose alternate start OR end dates occur
     * between the last cron job and right now
     *
     * @param PageCollection|BlockCollection $collection
     * @return PageCollection|BlockCollection
     */
    protected function _filterRecentAlternates($collection)
    {
        $now = $this->_getNow();
        $lastRefresh = $this->_getLastRefresh();
        return $collection
            ->addFieldToFilter('alternate', ['notnull' => true])
            ->addFieldToFilter('alternate_start', ['notnull' => true])
            ->addFieldToFilter('alternate_end', ['notnull' => true])
            ->addFieldToFilter(
                ['alternate_start', 'alternate_end'],
                [['from' => $lastRefresh, 'to' => $now], ['from' => $lastRefresh, 'to' => $now]]
            );
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
     * Get last refresh time with buffer, just for an edge case where an alternate_start/end
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

    /**
     * Log $msg if in debug mode
     * @param string $msg
     * @param array $context
     */
    protected function _debug($msg, array $context = [])
    {
        if ($this->_debugMode) {
            $this->_logger->info($msg); // For some reason $_logger->debug is not working
        }
    }
}
