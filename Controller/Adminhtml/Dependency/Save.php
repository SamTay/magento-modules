<?php
/**
 * @package     BlueAcorn\LayeredNavigation
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\LayeredNavigation\Controller\Adminhtml\Dependency;

use BlueAcorn\LayeredNavigation\Controller\Adminhtml\Dependency;

class Save extends Dependency
{
    /**
     * Save action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        // check post data
        $data = $this->getRequest()->getPostValue();
        if (!$data) {
            // go to grid
            return $resultRedirect->setPath('*/*/');
        }
        $id = $this->getRequest()->getParam('dependency_id');
        $model = $this->dependencyFactory->create()->load($id);
        if (!$model->getId() && $id) {
            $this->messageManager->addError(__('This filter dependency no longer exists.'));
            return $resultRedirect->setPath('*/*/');
        }

        // apply post data
        $model->setData($data);

        // try to save
        try {
            $model->save();
            $this->messageManager->addSuccess(__('Filter dependency saved successfully.'));
            // clear previously saved data from session
            $this->_session->setFormData(false);
            // check if 'Save and Continue'
            if ($this->getRequest()->getParam('back')) {
                return $resultRedirect->setPath('*/*/edit', ['dependency_id' => $model->getId()]);
            }
            // go to grid
            return $resultRedirect->setPath('*/*/');
        } catch (\Exception $e) {
            // display error message
            $this->messageManager->addError($e->getMessage());
            // save data in session
            $this->_session->setFormData($data);
            // redirect to edit form
            return $resultRedirect->setPath('*/*/edit', ['block_id' => $this->getRequest()->getParam('block_id')]);
        }
    }
}
