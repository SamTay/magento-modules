<?php
/**
 * @package     BlueAcorn\LayeredNavigation
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\LayeredNavigation\Block\Adminhtml\Dependency\Edit\Tab;

use BlueAcorn\LayeredNavigation\Model\Dependency;
use BlueAcorn\LayeredNavigation\Model\Dependency\Source\DependentOption;
use BlueAcorn\LayeredNavigation\Model\Dependency\Source\FilterAttribute;
use Magento\Framework\Data\Form\Element\Fieldset;
use Magento\Store\Model\System\Store as SystemStore;

abstract class AbstractTab extends \Magento\Backend\Block\Widget\Form\Generic
    implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /** @var DependentOption */
    protected $dependentOptionSource;

    /** @var FilterAttribute */
    protected $filterAttributeSource;

    /** @var string */
    protected $title;

    /** @var SystemStore */
    protected $systemStore;

    /**
     * Main constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param array $data
     * @param DependentOption $dependentOptionSource
     * @param FilterAttribute $filterAttributeSource
     * @param SystemStore $systemStore
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data,
        DependentOption $dependentOptionSource,
        FilterAttribute $filterAttributeSource,
        SystemStore $systemStore
    ) {
        parent::__construct($context, $registry, $formFactory, $data);
        $this->dependentOptionSource = $dependentOptionSource;
        $this->filterAttributeSource = $filterAttributeSource;
        $this->systemStore = $systemStore;
    }

    /**
     * Prepare fieldset
     *
     * @param Fieldset $fieldset
     */
    abstract protected function _prepareFieldset(Fieldset $fieldset);

    /**
     * Internal constructor
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setActive(true);
    }

    /**
     * Prepare label for tab
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __($this->title);
    }

    /**
     * Prepare title for tab
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __($this->title);
    }

    /**
     * Check if filter attribute id is set
     *
     * @return bool
     */
    public function isFilterAttributeSet()
    {
        return (bool)$this->getDependency()->getFilterAttributeId();
    }

    /**
     * Returns status flag about this tab hidden or not
     *
     * @return true
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * Get current dependency
     *
     * @return Dependency
     */
    public function getDependency()
    {
        return $this->_coreRegistry->registry('current_dependency');
    }

    /**
     * Initialize form fileds values
     *
     * @return $this
     */
    protected function _initFormValues()
    {
        $this->getForm()->addValues($this->getDependency()->getData());
        return parent::_initFormValues();
    }

    /**
     * Prepare form before rendering HTML
     *
     * @return $this
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareForm()
    {
        $dependency = $this->getDependency();

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create(
            ['data' => ['id' => 'edit_form', 'action' => $this->getData('action'), 'method' => 'post']]
        );

        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __($this->title)]);
        if ($dependency->getId()) {
            $fieldset->addField('dependency_id', 'hidden', ['name' => 'dependency_id']);
        }

        $this->_prepareFieldset($fieldset);
        $this->setForm($form);
        return parent::_prepareForm();
    }
}
