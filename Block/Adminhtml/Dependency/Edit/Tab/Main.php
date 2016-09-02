<?php
/**
 * @package     BlueAcorn\LayeredNavigation
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\LayeredNavigation\Block\Adminhtml\Dependency\Edit\Tab;

use Magento\Framework\Data\Form\Element\Fieldset;

class Main extends AbstractTab
{
    /**
     * Initialize
     */
    public function _construct()
    {
        $this->title = 'Dependency Properties';
        $this->tabCode = 'main';
        parent::_construct();
    }

    /**
     * Returns status flag about this tab can be showen or not
     *
     * @return true
     */
    public function canShowTab()
    {
        return $this->isFilterAttributeSet();
    }

    /**
     * @param Fieldset $fieldset
     */
    protected function _prepareFieldset(Fieldset $fieldset)
    {
        $dependency = $this->getDependency();

        $fieldset->addField(
            'attribute_id',
            'select',
            [
                'name' => 'attribute_id',
                'label' => __('Filter Attribute'),
                'title' => __('Filter Attribute'),
                'disabled' => true,
                'values' => $this->filterAttributeSource->toOptionArray()
            ]
        );

        $fieldset->addField(
            'option_id',
            'select',
            [
                'name' => 'option_id',
                'label' => __('Depends On Option'),
                'title' => __('Depends On Option'),
                'required' => true,
                'values' => $this->dependentOptionSource->toOptionArray($dependency->getFilterAttributeId())
            ]
        );

        // Default to enabled status
        if (!$dependency->getId()) {
            $dependency->setStatus(1);
        }
        $fieldset->addField(
            'status',
            'select',
            [
                'name' => 'status',
                'label' => __('Status'),
                'title' => __('Status'),
                'required' => true,
                'values' => $this->statusSource->toOptionArray()
            ]
        );

        /**
         * Check is single store mode
         */
        if (!$this->_storeManager->isSingleStoreMode()) {
            $field = $fieldset->addField(
                'store_id',
                'multiselect',
                [
                    'name' => 'stores[]',
                    'label' => __('Store View'),
                    'title' => __('Store View'),
                    'required' => true,
                    'values' => $this->systemStore->getStoreValuesForForm(false, true),
                ]
            );
            $renderer = $this->getLayout()->createBlock(
                'Magento\Backend\Block\Store\Switcher\Form\Renderer\Fieldset\Element'
            );
            $field->setRenderer($renderer);
        } else {
            $fieldset->addField(
                'store_id',
                'hidden',
                ['name' => 'stores[]', 'value' => $this->_storeManager->getStore(true)->getId()]
            );
            $dependency->setStoreId($this->_storeManager->getStore(true)->getId());
        }
    }
}
