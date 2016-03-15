<?php
/**
 * @package     BlueAcorn\ContentScheduler
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\ContentScheduler\Observer\Adminhtml\CmsPage;

use BlueAcorn\ContentScheduler\Model\Config\Source\CmsPage as CmsPageSource;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class Form implements ObserverInterface
{
    /**
     * @var AuthorizationInterface
     */
    protected $_authorization;

    /**
     * @var TimezoneInterface
     */
    protected $_localeDate;

    /**
     * @var CmsPageSource
     */
    protected $_alternateSource;

    /**
     * @var Registry
     */
    protected $_coreRegistry;
    /**
     * Form constructor.
     * @param AuthorizationInterface $authorization
     * @param TimezoneInterface $localeDate
     * @param CmsPageSource $alternateSource
     * @param Registry $registry
     */
    public function __construct(
        AuthorizationInterface $authorization,
        TimezoneInterface $localeDate,
        CmsPageSource $alternateSource,
        Registry $registry
    ) {
        $this->_authorization = $authorization;
        $this->_localeDate = $localeDate;
        $this->_alternateSource = $alternateSource;
        $this->_coreRegistry = $registry;
    }

    public function execute(EventObserver $observer)
    {
        $form = $observer->getEvent()->getForm();
        $isElementDisabled = !$this->_authorization->isAllowed('Magento_Cms::save');
        $dateFormat = $this->_localeDate->getDateFormat(\IntlDateFormatter::SHORT);
        $timeFormat = $this->_localeDate->getTimeFormat(\IntlDateFormatter::SHORT);
        /* @var $model \Magento\Cms\Model\Page */
        $model = $this->_coreRegistry->registry('cms_page');

        $scheduleFieldset = $form->addFieldset(
            'schedule_fieldset',
            ['legend' => __('Schedule'), 'class' => 'fieldset-wide', 'disabled' => $isElementDisabled]
        );

        $scheduleFieldset->addField(
            'alternate',
            'select',
            [
                'name' => 'alternate',
                'label' => __('Alternate Page'),
                'values' => $this->_alternateSource->toOptionArray(true, $model->getId()),
                'disabled' => $isElementDisabled
            ]
        );

        $scheduleFieldset->addField(
            'alternate_start',
            'date',
            [
                'name' => 'alternate_start',
                'label' => __('Alternate Page Start'),
                'date_format' => $dateFormat,
                'time_format' => $timeFormat,
                'disabled' => $isElementDisabled,
                'class' => 'validate-date validate-date-range date-range-alternate-from'
            ]
        );

        $scheduleFieldset->addField(
            'alternate_end',
            'date',
            [
                'name' => 'alternate_end',
                'label' => __('Alternate Page End'),
                'date_format' => $dateFormat,
                'time_format' => $timeFormat,
                'disabled' => $isElementDisabled,
                'class' => 'validate-date validate-date-range date-range-alternate-to'
            ]
        );
    }
}
