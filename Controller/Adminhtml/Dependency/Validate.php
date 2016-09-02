<?php
/**
 * @package     BlueAcorn\LayeredNavigation
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\LayeredNavigation\Controller\Adminhtml\Dependency;

use BlueAcorn\LayeredNavigation\Controller\Adminhtml\Dependency;
use BlueAcorn\LayeredNavigation\Model\DependencyFactory;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Registry;
use BlueAcorn\LayeredNavigation\Model\ResourceModel\Dependency\CollectionFactory as DependencyCollectionFactory;

/**
 * Uniqueness checks at resource level result in proper exception messages in adminhtml,
 * but this allows for us to include extra data (existing entity ID)
 */
class Validate extends Dependency
{
    /** @var DependencyCollectionFactory */
    private $collectionFactory;

    /**
     * Validate constructor.
     * @param Context $context
     * @param DependencyFactory $dependencyFactory
     * @param Registry $registry
     * @param DependencyCollectionFactory $collectionFactory
     */
    public function __construct(
        Context $context,
        DependencyFactory $dependencyFactory,
        Registry $registry,
        DependencyCollectionFactory $collectionFactory
    ) {
        parent::__construct($context, $dependencyFactory, $registry);
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        // init response
        $error = false;
        $message = '';

        // get request parameters
        $dependencyId = $this->getRequest()->getParam('dependency_id');
        $attributeId = $this->getRequest()->getParam('attribute_id');
        $optionId = $this->getRequest()->getParam('option_id');
        $attributeId = ($dependencyId && !$attributeId)
            ? $this->getAttributeId($dependencyId)
            : $attributeId;

        // check for duplicates
        $candidate = $this->collectionFactory->create()
            ->addFieldToFilter('attribute_id', $attributeId)
            ->addFieldToFilter('option_id', $optionId)
            ->getFirstItem();
        if ($candidate->getId() && $candidate->getId() != $dependencyId) {
            $message = __('This dependency already exists with ID %1', $candidate->getId());
            $error = true;
        }
        return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData([
            'message' => $message,
            'error' => $error
        ]);
    }

    /**
     * Get attribute id from dependency id
     *
     * @param $dependencyId
     * @return null|int
     */
    private function getAttributeId($dependencyId)
    {
        return $this->collectionFactory->create()
            ->addFieldToSelect('attribute_id')
            ->addFieldToFilter('dependency_id', $dependencyId)
            ->getFirstItem()
            ->getAttributeId();
    }
}
