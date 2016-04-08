<?php
/**
 * @package     BlueAcorn\ContentPublisher
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\ContentPublisher\Observer\Adminhtml\CmsPage;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

/**
 * Class Form
 * Observes adminhtml_cms_page_edit_tab_main_prepare_form
 * Purpose: Inject new attributes into adminhtml form
 */
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
     * @var Registry
     */
    protected $_coreRegistry;

    /**
     * @param AuthorizationInterface $authorization
     * @param TimezoneInterface $localeDate
     * @param Registry $registry
     */
    public function __construct(
        AuthorizationInterface $authorization,
        TimezoneInterface $localeDate,
        Registry $registry
    ) {
        $this->_authorization = $authorization;
        $this->_localeDate = $localeDate;
        $this->_coreRegistry = $registry;
    }

    /**
     * Add publisher fieldset to cms page edit
     * @param EventObserver $observer
     */
    public function execute(EventObserver $observer)
    {
        /** @var \Magento\Framework\Data\Form $form */
        $form = $observer->getEvent()->getForm();
        $isElementDisabled = !$this->_authorization->isAllowed('Magento_Cms::save');
        $dateFormat = $this->_localeDate->getDateFormat(\IntlDateFormatter::SHORT);
        $timeFormat = $this->_localeDate->getTimeFormat(\IntlDateFormatter::SHORT);

        $publisherFieldset = $form->addFieldset(
            'publisher_fieldset',
            ['legend' => __('Publisher'), 'class' => 'fieldset-wide', 'disabled' => $isElementDisabled]
        );

        $publisherFieldset->addField(
            'publish_start',
            'date',
            [
                'name' => 'publish_start',
                'label' => __('Publish Start Date'),
                'date_format' => $dateFormat,
                'time_format' => $timeFormat,
                'disabled' => $isElementDisabled,
                'class' => 'validate-date validate-date-range date-range-publish-from'
            ]
        );

        $publisherFieldset->addField(
            'publish_end',
            'date',
            [
                'name' => 'publish_end',
                'label' => __('Publish End Date'),
                'date_format' => $dateFormat,
                'time_format' => $timeFormat,
                'disabled' => $isElementDisabled,
                'class' => 'validate-date validate-date-range date-range-publish-to',
                'note' => __('If you want to keep a page published indefinitely, leave the start and end dates empty.'
                    . ' If a "Publish End Date" exists, the page will remain disabled after the end date has passed.')
            ]
        );
    }
}
