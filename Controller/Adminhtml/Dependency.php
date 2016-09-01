<?php
/**
 * @package     BlueAcorn\LayeredNavigation
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\LayeredNavigation\Controller\Adminhtml;

use BlueAcorn\LayeredNavigation\Model\DependencyFactory;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;

abstract class Dependency extends \Magento\Backend\App\Action
{
    /** @var Registry */
    protected $registry = null;

    /** @var DependencyFactory */
    protected $dependencyFactory;

    /**
     * @param Context $context
     * @param DependencyFactory $dependencyFactory
     * @param Registry $registry
     */
    public function __construct(
        Context $context,
        DependencyFactory $dependencyFactory,
        Registry $registry
    ) {
        $this->registry = $registry;
        $this->dependencyFactory = $dependencyFactory;
        parent::__construct($context);
    }

    /**
     * Init action
     *
     * @return $this
     */
    protected function initAction()
    {
        $this->_view->loadLayout();
        $this->_addBreadcrumb(__('Layered Navigation'), __('Layered Navigation'));
        $this->_addBreadcrumb(__('Filter Dependencies'), __('Filter Dependencies'));
        $this->_setActiveMenu('BlueAcorn_LayeredNavigation::filter_dependency');
        return $this;
    }

    /**
     * Load Dependency from request
     *
     * @param string $idFieldName
     * @return \BlueAcorn\LayeredNavigation\Model\Dependency
     */
    protected function initDependency($idFieldName = 'dependency_id')
    {
        $dependencyId = (int)$this->getRequest()->getParam($idFieldName);
        $dependency = $this->dependencyFactory->create();
        if ($dependencyId) {
            $dependency->load($dependencyId);
            if (!$dependency->getId()) {
                $this->messageManager->addError(__('Please specify a valid dependency ID.'));
                return false;
            }
        } else {
            // If dependency ID not provided in query, check if initial attribute_id exists
            $attributeId = $this->getRequest()->getParam('attribute_id');
            if ($attributeId) {
                $dependency->setFilterAttributeId($attributeId);
            }
        }
        if (!$this->registry->registry('current_dependency')) {
            $this->registry->register('current_dependency', $dependency);
        }
        return $dependency;
    }

    /**
     * Check permissions
     *
     * @return boolean
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('BlueAcorn_LayeredNavigation::filter_dependencies');
    }
}

