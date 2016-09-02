<?php
/**
 * @package     BlueAcorn\LayeredNavigation
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\LayeredNavigation\Block\Adminhtml\Dependency\Edit\Tab;

use Magento\Framework\Data\Form\Element\Fieldset;

class Settings extends AbstractTab
{
    /**
     * Initialize
     */
    protected function _construct()
    {
        $this->title = 'Settings';
        $this->tabCode = 'settings';
        parent::_construct();
    }

    /**
     * Returns status flag about this tab can be showen or not
     *
     * @return true
     */
    public function canShowTab()
    {
        return !$this->isFilterAttributeSet();
    }

    /**
     * Add fields
     *
     * @param Fieldset $fieldset
     */
    protected function _prepareFieldset(Fieldset $fieldset)
    {
        $this->_addElementTypes($fieldset);

        $fieldset->addField(
            'attribute_id',
            'select',
            [
                'name' => 'attribute_id',
                'label' => __('Filter Attribute'),
                'title' => __('Filter Attribute'),
                'required' => true,
                'values' => $this->filterAttributeSource->toOptionArray()
            ]
        );

        $continueButton = $this->getLayout()->createBlock('Magento\Backend\Block\Widget\Button')
            ->setData([
                'label' => __('Continue'),
                'onclick' => "setFilterAttribute('" . $this->getContinueUrl() . "', 'attribute_id')",
                'class' => 'save',
            ]);
        $fieldset->addField('continue_button', 'note', ['text' => $continueButton->toHtml()]);
    }

    /**
     * Return url for continue button
     *
     * @return string
     */
    public function getContinueUrl()
    {
        // TODO Test URL !!!!!
        return $this->getUrl(
            '*/*/*',
            ['_current' => true, 'attribute_id' => '<%- data.attribute_id %>']
        );
    }
}
