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
use Magento\Framework\DataObject\Factory as DataObjectFactory;

class Validate extends Dependency
{
    /** @var DependencyCollectionFactory */
    private $collectionFactory;

    /** @var DataObjectFactory */
    private $dataObjectFactory;

    /**
     * Validate constructor.
     * @param Context $context
     * @param DependencyFactory $dependencyFactory
     * @param Registry $registry
     * @param DependencyCollectionFactory $collectionFactory
     * @param DataObjectFactory $dataObjectFactory
     */
    public function __construct(
        Context $context,
        DependencyFactory $dependencyFactory,
        Registry $registry,
        DependencyCollectionFactory $collectionFactory,
        DataObjectFactory $dataObjectFactory
    ) {
        parent::__construct($context, $dependencyFactory, $registry);
        $this->collectionFactory = $collectionFactory;
        $this->dataObjectFactory = $dataObjectFactory;
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        // init response
        $response = $this->dataObjectFactory->create(['error' => false]);

        // get request parameters
        $dependencyId = $this->getRequest()->getParam('dependency_id');
        $attributeId = $this->getRequest()->getParam('attribute_id');
        $optionId = $this->getRequest()->getParam('option_id');

        // check for duplicates
        $candidate = $this->collectionFactory->create()
            ->addFieldToFilter('attribute_id', $attributeId)
            ->addFieldToFilter('option_id', $optionId)
            ->getFirstItem();
        if ($candidate->getId() && $candidate->getId() != $dependencyId) {
            $response->setMessage(__('This dependency already exists with ID %1', $candidate->getId()))
                ->setError(true);
        }
        return $this->resultFactory->create(ResultFactory::TYPE_JSON)
            ->setJsonData($response->toJson());
    }
}
