<?php
/**
 * @package     BlueAcorn\ContentScheduler
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\ContentScheduler\Observer\Adminhtml\CmsPage;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use BlueAcorn\ContentScheduler\Helper\Adminhtml\Form as Helper;

/**
 * Class Form
 * Observes adminhtml_cms_page_edit_tab_main_prepare_form
 * Purpose: Inject new attributes into adminhtml form
 * @package BlueAcorn\ContentScheduler\Observer\Adminhtml\CmsPage
 */
class Form implements ObserverInterface
{
    /**
     * @var Helper
     */
    protected $_helper;

    /**
     * Form constructor.
     * @param Helper $helper
     */
    public function __construct(Helper $helper)
    {
        $this->_helper = $helper;
    }

    public function execute(EventObserver $observer)
    {
        $form = $observer->getEvent()->getForm();
        $this->_helper->addScheduleFieldsetToPage($form);
    }
}
