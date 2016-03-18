<?php
/**
 * @package     BlueAcorn\ContentScheduler
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\ContentScheduler\Plugin\Cms\Helper;

use BlueAcorn\ContentScheduler\Helper\Scheduler;
use Magento\Cms\Helper\Page as PageHelper;
use Magento\Cms\Model\Page as PageModel;
use Magento\Framework\App\Action\Action;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Page
 * Purpose: Swap CMS Page content to alternate
 */
class Page
{
    /**
     * @var PageModel
     */
    protected $_page;
    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * @var Scheduler
     */
    protected $_scheduler;

    /**
     * Page constructor.
     * @param PageModel $page
     * @param StoreManagerInterface $storeManager
     * @param Scheduler $scheduler
     */
    public function __construct(
        PageModel $page,
        StoreManagerInterface $storeManager,
        Scheduler $scheduler
    ) {
        $this->_page = $page;
        $this->_storeManager = $storeManager;
        $this->_scheduler = $scheduler;
    }

    /**
     * Swap scheduled alternate CMS page
     *
     * @param PageHelper $subject
     * @param Action $action
     * @param null|int|string $pageId
     * @return array|null
     */
    public function beforePrepareResultPage(
        PageHelper $subject,
        Action $action,
        $pageId = null
    ) {
        // Check page identifier before attempting load
        if (!$this->_checkIdentifier($pageId)) {
            return; // If before-listener plugin returns falsey value, original arguments are passed along
        }
        return [$action, $this->_scheduler->getScheduledPageId($this->_page)];
    }

    /**
     * Logic taken from start of \Magento\Cms\Helper\Page::prepareResultPage
     *
     * @param null|int|string $pageId
     * @return bool
     */
    protected function _checkIdentifier($pageId)
    {
        if ($pageId !== null && $pageId !== $this->_page->getId()) {
            $delimiterPosition = strrpos($pageId, '|');
            if ($delimiterPosition) {
                $pageId = substr($pageId, 0, $delimiterPosition);
            }

            $this->_page->setStoreId($this->_storeManager->getStore()->getId());
            if (!$this->_page->load($pageId)) {
                return false;
            }
        }

        if (!$this->_page->getId()) {
            return false;
        }

        return true;
    }
}