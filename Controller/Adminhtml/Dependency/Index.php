<?php
/**
 * @package     BlueAcorn\LayeredNavigation
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\LayeredNavigation\Controller\Adminhtml\Dependency;

use BlueAcorn\LayeredNavigation\Controller\Adminhtml\Dependency;

class Index extends Dependency
{
    /**
     * Index action
     */
    public function execute()
    {
        $this->initAction();
        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Filter Dependencies'));
        $this->_view->renderLayout();
    }

    /**
     * Is the user allowed to view the blog post grid.
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('BlueAcorn_LayeredNavigation::filter_dependency');
    }


}