<?php
/**
 * @package     BlueAcorn\ContentScheduler
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\ContentScheduler\Observer\Block;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Cms\Model\Block as BlockModel;
use BlueAcorn\ContentScheduler\Helper\Scheduler;

/**
 * Class ToHtmlBefore
 * Observes view_block_abstract_to_html_before
 * Purpose: Swap CMS block content to alternate
 */
class ToHtmlBefore implements ObserverInterface
{
    /**
     * @var Scheduler
     */
    protected $_scheduler;

    /**
     * @var BlockModel
     */
    protected $_block;

    /**
     * Form constructor.
     * @param Scheduler $scheduler
     * @param BlockModel $block
     */
    public function __construct(Scheduler $scheduler, BlockModel $block)
    {
        $this->_scheduler = $scheduler;
        $this->_block = $block;
    }

    /**
     * Check if block is CMS -- if so, swap with alternate content
     *
     * M2 still only has abstract block event, no granularity to only observe cms block events
     * @param EventObserver $observer
     */
    public function execute(EventObserver $observer)
    {
        $block = $observer->getEvent()->getBlock();
        if ($block->getType() == 'Magento\Cms\Block\Block') {
            $block->setBlockId($this->_scheduler->getScheduledBlockId($block->getBlockId()));
        }
    }
}
