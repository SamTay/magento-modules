<?php
/**
 * @package     BlueAcorn\ContentScheduler
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\ContentScheduler\Helper\Adminhtml;

use Magento\Framework\AuthorizationInterface;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use BlueAcorn\ContentScheduler\Model\Config\Source\Alternate\Page as AlternatePageSource;
use BlueAcorn\ContentScheduler\Model\Config\Source\Alternate\Block as AlternateBlockSource;

/**
 * Class Form
 * Helper to add scheduler fields to block & page admin forms
 */
class Form
{
    const FIELDSET_ID = 'schedule_fieldset';

    /**
     * @var AuthorizationInterface
     */
    protected $_authorization;

    /**
     * @var TimezoneInterface
     */
    protected $_localeDate;

    /**
     * @var AlternatePageSource
     */
    protected $_alternatePageSource;

    /**
     * @var AlternateBlockSource
     */
    protected $_alternateBlockSource;

    /**
     * @var Registry
     */
    protected $_coreRegistry;

    /**
     * @var array
     */
    protected $_allowedEntityTypes = ['page', 'block'];

    /**
     * @param AuthorizationInterface $authorization
     * @param TimezoneInterface $localeDate
     * @param Registry $registry
     * @param AlternatePageSource $alternatePageSource
     * @param AlternateBlockSource $alternateBlockSource
     */
    public function __construct(
        AuthorizationInterface $authorization,
        TimezoneInterface $localeDate,
        Registry $registry,
        AlternatePageSource $alternatePageSource,
        AlternateBlockSource $alternateBlockSource
    ) {
        $this->_authorization = $authorization;
        $this->_localeDate = $localeDate;
        $this->_coreRegistry = $registry;
        $this->_alternatePageSource = $alternatePageSource;
        $this->_alternateBlockSource = $alternateBlockSource;
    }

    /**
     * Add schedule fieldset to adminhtml cms page form
     *
     * @param \Magento\Framework\Data\Form $form
     * @return Form
     * @throws Exception
     */
    public function addScheduleFieldsetToPage(\Magento\Framework\Data\Form $form)
    {
        return $this->_addScheduleFieldset($form, 'page');
    }

    /**
     * Add schedule fieldset to adminhtml cms block form
     *
     * @param \Magento\Framework\Data\Form $form
     * @return Form
     * @throws Exception
     */
    public function addScheduleFieldsetToBlock(\Magento\Framework\Data\Form $form)
    {
        return $this->_addScheduleFieldset($form, 'block');
    }

    /**
     * Add schedule fieldset to $form for either block/page
     *
     * @param \Magento\Framework\Data\Form $form
     * @param string $entityType ('page'|'block')
     * @return $this
     * @throws Exception
     */
    protected function _addScheduleFieldset(\Magento\Framework\Data\Form $form, $entityType)
    {
        if (!in_array($entityType, $this->_allowedEntityTypes)) {
            throw new Exception('Invalid method argument.');
        }
        $isElementDisabled = !$this->_authorization->isAllowed('Magento_Cms::save');
        $dateFormat = $this->_localeDate->getDateFormat(\IntlDateFormatter::SHORT);
        $timeFormat = $this->_localeDate->getTimeFormat(\IntlDateFormatter::SHORT);
        $model = $this->_coreRegistry->registry('cms_' . $entityType);
        $entityName = ucfirst($entityType);
        $alternateSource = '_alternate' . $entityName . 'Source';

        $scheduleFieldset = $form->addFieldset(
            self::FIELDSET_ID,
            ['legend' => __('Scheduler'), 'class' => 'fieldset-wide', 'disabled' => $isElementDisabled]
        );

        $scheduleFieldset->addField(
            'alternate',
            'select',
            [
                'name' => 'alternate',
                'label' => __('Alternate %1', $entityName),
                'values' => $this->{$alternateSource}->toOptionArray(true, $model->getId()),
                'disabled' => $isElementDisabled
            ]
        );

        $scheduleFieldset->addField(
            'alternate_start',
            'date',
            [
                'name' => 'alternate_start',
                'label' => __('Alternate %1 Start', $entityName),
                'date_format' => $dateFormat,
                'time_format' => $timeFormat,
                'disabled' => $isElementDisabled,
                'style' => $entityType == 'block' ? 'margin-right:40px;height:33px;width:160px;' : '',
                'class' => 'validate-date validate-date-range date-range-alternate-from',
                'note' => __('The cache invalidation that allows the alternate content to start may take up to five minutes.'
                    . ' For best results, use times at five minute intervals such as :00, :05, :10, etc.')
            ]
        );

        $scheduleFieldset->addField(
            'alternate_end',
            'date',
            [
                'name' => 'alternate_end',
                'label' => __('Alternate %1 End', $entityName),
                'date_format' => $dateFormat,
                'time_format' => $timeFormat,
                'disabled' => $isElementDisabled,
                'style' => $entityType == 'block' ? 'margin-right:40px;height:33px;width:160px;' : '',
                'class' => 'validate-date validate-date-range date-range-alternate-to',
                'note' => __('The cache invalidation that allows the alternate content to stop may take up to five minutes.'
                    . ' For best results, use times at five minute intervals such as :00, :05, :10, etc.')
            ]
        );

        return $this;
    }
}
