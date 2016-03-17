<?php
/**
 * @package     BlueAcorn\ContentScheduler
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\ContentScheduler\Helper;

use Magento\Cms\Model\Page as PageModel;
use Magento\Cms\Model\PageFactory;
use Magento\Cms\Model\Block as BlockModel;
use Magento\Cms\Model\BlockFactory;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class Scheduler extends AbstractHelper
{
    /**
     * @var PageFactory
     */
    protected $_pageFactory;

    /**
     * @var BlockFactory
     */
    protected $_blockFactory;

    /**
     * @var TimezoneInterface
     */
    protected $_localeDate;

    /**
     * Construct
     *
     * @param PageFactory $pageFactory
     * @param BlockFactory $blockFactory
     * @param TimezoneInterface $localeDate
     * @param Context $context
     */
    public function __construct(
        PageFactory $pageFactory,
        BlockFactory $blockFactory,
        TimezoneInterface $localeDate,
        Context $context
    ) {
        $this->_localeDate = $localeDate;
        $this->_pageFactory = $pageFactory;
        $this->_blockFactory = $blockFactory;
        parent::__construct($context);
    }

    /**
     * Get scheduled alternate content for $cmsEntity
     * Explicitly removed recursion for alternate chains to keep in line with requirements
     * -- easy extension of this method to allow chaining in the future
     *
     * TODO: When Magento releases fix for CMS implementations, revert commit to use interfaces/repositories,etc.
     *
     * TODO: Still needs GMT offset. Currently assuming admin set these dates within the
     * system configured timezone -- as long as that is true, this works as expected.
     *
     * @param PageModel|BlockModel $cmsEntity
     * @return BlockModel|PageModel
     * @throws Exception
     */
    public function getScheduledContent($cmsEntity)
    {
        $entityType = $this->_getEntityType($cmsEntity);
        if (!$entityType) {
            throw new Exception('Invalid argument');
        }
        $factory = '_' . $entityType . 'Factory';

        $alternate = null;
        $alternateId = $cmsEntity->getAlternate();
        $start = $this->_localeDate->scopeDate(null, $cmsEntity->getAlternateStart(), true)->getTimestamp();
        $end = $this->_localeDate->scopeDate(null, $cmsEntity->getAlternateEnd(), true)->getTimeStamp();
        $now = $this->_localeDate->scopeDate(null, null, true)->getTimestamp();
        if ($alternateId
            && $start
            && $end
            && $start <= $now
            && $now < $end
        ) {
            $alternate = $this->{$factory}->create()->load($alternateId);
        }

        return ($alternate && $alternate->getId()) ? $alternate : $cmsEntity;
    }

    /**
     * Get scheduled alternate CMS page id
     *
     * @param int|string|PageModel $page
     * @return int
     */
    public function getScheduledPageId($page)
    {
        if (!$page instanceof PageModel) {
            $page = $this->_pageFactory->create()->load($page);
        }
        return $this->getScheduledContent($page)->getId();
    }

    /**
     * Get scheduled alternate CMS block id
     *
     * @param int|string|BlockModel $block
     * @return int
     * @throws Exception
     */
    public function getScheduledBlockId($block)
    {
        if (!$block instanceof BlockModel) {
            $block = $this->_blockFactory->create()->load($block);
        }
        return $this->getScheduledContent($block)->getId();
    }

    /**
     * Check entity type
     *
     * @param $cmsEntity
     * @return null|string
     */
    protected function _getEntityType($cmsEntity)
    {
        $entityType = null;
        if ($cmsEntity instanceof PageModel) {
            $entityType = 'page';
        }
        if ($cmsEntity instanceof BlockModel) {
            $entityType = 'block';
        }
        return $entityType;
    }
}
