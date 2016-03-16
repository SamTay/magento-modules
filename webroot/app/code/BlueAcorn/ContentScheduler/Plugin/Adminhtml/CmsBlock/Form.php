<?php
/**
 * @package     BlueAcorn\ContentScheduler
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\ContentScheduler\Plugin\Adminhtml\CmsBlock;

use BlueAcorn\ContentScheduler\Helper\Adminhtml\Form as Helper;
use Magento\Framework\Registry;

/**
 * Class Form
 * Plugin for \Magento\Cms\Block\Adminhtml\Block\Edit\Form
 * Purpose: Inject new attributes into adminhtml form
 * @package BlueAcorn\ContentScheduler\Plugin\Adminhtml\CmsBlock
 */
class Form
{
    /**
     * @var Helper
     */
    protected $_helper;

    /**
     * @var Registry
     */
    protected $_coreRegistry;

    /**
     * @var array
     */
    protected $_scheduleElements = [
        'alternate',
        'alternate_start',
        'alternate_end'
    ];

    /**
     * Form constructor.
     * @param Helper $helper
     * @param Registry $registry
     */
    public function __construct(Helper $helper, Registry $registry)
    {
        $this->_coreRegistry = $registry;
        $this->_helper = $helper;
    }

    /**
     * Plugin to add schedule field set before setting form
     * Magento doesn't issue the same events like it does for Page forms, so hooking into a public
     * method got weird.
     *
     * @param \Magento\Cms\Block\Adminhtml\Block\Edit\Form $subject
     * @param \Magento\Framework\Data\Form $form
     */
    public function beforeSetForm(
        \Magento\Cms\Block\Adminhtml\Block\Edit\Form $subject,
        \Magento\Framework\Data\Form $form
    ) {
        if (!$form->getElement(Helper::FIELDSET_ID)) {
            $this->_helper->addScheduleFieldsetToBlock($form);
            /** @var \Magento\Cms\Model\Block $model */
            $model = $this->_coreRegistry->registry('cms_block');
            $form->addValues($model->toArray($this->_scheduleElements));
        }
    }
}
