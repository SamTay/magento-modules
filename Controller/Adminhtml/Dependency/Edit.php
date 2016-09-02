<?php
/**
 * @package     BlueAcorn\LayeredNavigation
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\LayeredNavigation\Controller\Adminhtml\Dependency;

use BlueAcorn\LayeredNavigation\Controller\Adminhtml\Dependency;

class Edit extends Dependency
{
    /**
     * Edit filter dependency action
     *
     * @return void
     */
    public function execute()
    {
        $dependency = $this->initDependency();
        if (!$dependency) {
            $this->_redirect('*/*/');
            return;
        }

        $this->initAction();
        $title = $dependency->getFilterAttributeId()
            ? __('Dependency for %1', $dependency->getFilterAttribute()->getDefaultFrontendLabel())
            : __('New Dependency');
        $this->_view->getPage()->getConfig()->getTitle()->prepend($title);
        $this->_view->renderLayout();
    }
}
