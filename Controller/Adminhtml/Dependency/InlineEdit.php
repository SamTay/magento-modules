<?php
/**
 * @package     BlueAcorn\LayeredNavigation
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\LayeredNavigation\Controller\Adminhtml\Dependency;

use BlueAcorn\LayeredNavigation\Controller\Adminhtml\Dependency;
use BlueAcorn\LayeredNavigation\Model\Dependency as DependencyModel;
use Magento\Framework\Controller\ResultFactory;

class InlineEdit extends Dependency
{
    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $error = false;
        $messages = [];

        if ($this->getRequest()->getParam('isAjax')) {
            $postItems = $this->getRequest()->getParam('items', []);
            if (!count($postItems)) {
                $messages[] = __('Please correct the requested data.');
                $error = true;
            } else {
                foreach ($postItems as $dependencyId => $postData) {
                    /** @var DependencyModel $dependency */
                    $dependency = $this->dependencyFactory->create()->load($dependencyId);
                    try {
                        $dependency->setData(array_merge($dependency->getData(), $postData));
                        $dependency->save();
                    } catch (\Exception $e) {
                        $messages[] = __(
                            'Error with Dependency #%1: %2',
                            $dependencyId,
                            $e->getMessage()
                        );
                        $error = true;
                    }
                }
            }
        }

        // return json response
        return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData([
            'messages' => $messages,
            'error' => $error
        ]);
    }
}
