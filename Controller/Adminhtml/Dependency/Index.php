<?php
/**
 * @package     BlueAcorn\LayeredNavigation
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\LayeredNavigation\Controller\Adminhtml\Dependency;

use BlueAcorn\LayeredNavigation\Controller\Adminhtml\Dependency;
use Magento\Framework\View\Result\PageFactory;
use Magento\Backend\App\Action\Context;
use BlueAcorn\LayeredNavigation\Model\DependencyFactory;
use Magento\Framework\Registry;

class Index extends Dependency
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @param Context $context
     * @param DependencyFactory $dependencyFactory
     * @param Registry $registry
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        DependencyFactory $dependencyFactory,
        Registry $registry,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context, $dependencyFactory, $registry);
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * Index action
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        // TODO see if page factory an just go in parent
        $this->initPage($resultPage)->getConfig()->getTitle()->prepend(__('Filter Dependencies'));

        return $resultPage;
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