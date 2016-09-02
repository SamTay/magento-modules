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
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function execute()
    {
        $model = $this->initDependency();
        $data = $this->getRequest()->getPostValue();
        if (!$model || !$data) {
            // go back to grid with error message
            return $this->_redirect('*/*/');
        }
        // apply post data
        $model->addData($data); // todo test empty store ids
        try {
            $model->save();
            $this->messageManager->addSuccess(__('Filter dependency saved successfully.'));
            $this->_session->setFormData(false);
            // check if 'Save and Continue'
            if ($this->getRequest()->getParam('back')) {
                return $this->_redirect('*/*/edit', ['dependency_id' => $model->getId()]);
            }
            // otherwise go to grid
            return $this->_redirect('*/*/');
        } catch (\Exception $exception) {
            $this->messageManager->addError($exception->getMessage());
            $this->_logger->critical($exception);
            return $this->_redirect('*/*/edit', ['_current' => true, 'dependency_id' => $model->getId()]);
        }
        return $this->_redirect('*/*/');
    }
}
