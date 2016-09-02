<?php
/**
 * @package     BlueAcorn\LayeredNavigation
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\LayeredNavigation\Controller\Adminhtml\Dependency;

use BlueAcorn\LayeredNavigation\Controller\Adminhtml\Dependency;

class Delete extends Dependency
{
    /**
     * Delete action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        // check if we know what should be deleted
        $id = $this->getRequest()->getParam('dependency_id');
        if ($id) {
            try {
                $this->dependencyFactory->create()->load($id)->delete();
                $this->messageManager->addSuccess(__('Filter dependency deleted successfully.'));
                // go to grid
                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
                // go to edit form
                return $resultRedirect->setPath('*/*/edit', ['dependency_id' => $id]);
            }
        }
        $this->messageManager->addError(__('We can\'t find a filter dependency to delete.'));
        return $resultRedirect->setPath('*/*/');
    }
}
