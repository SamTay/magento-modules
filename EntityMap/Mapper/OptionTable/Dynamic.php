<?php
/**
 * @package     BlueAcorn\EntityMap
 * @version     0.2.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\EntityMap\Mapper\OptionTable;

use BlueAcorn\EntityMap\Escape;
use BlueAcorn\EntityMap\Mapper\LabelToOption;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Api\Data\AttributeOptionInterfaceFactory;
use Magento\Eav\Api\AttributeOptionManagementInterface;
use Magento\Eav\Api\Data\AttributeOptionInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Store;
use Magento\Framework\Api\DataObjectHelper;

/**
 * Class Dynamic
 *
 * Maps labels "Blue,Bluish Greenish,Green"
 * to option IDs "21,26,22"
 * And will dynamically create [option/value] [Blueish Greenish/26] if doesn't exist
 *
 * Note: External ERPs likely do not have the same concept of multiple stores like Magento does. Therefore
 * this option creator assumes the same store labels are used across all stores.
 */
class Dynamic extends Strict
{
    const SOURCE_MODEL_TABLE = 'Magento\Eav\Model\Entity\Attribute\Source\Table';

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var AttributeOptionManagementInterface
     */
    protected $attributeOptionManagement;

    /**
     * @var AttributeOptionInterfaceFactory
     */
    protected $optionDataFactory;

    /**
     * @var DataObjectHelper
     */
    protected $dataObjectHelper;

    /**
     * Strict constructor.
     *
     * @param AttributeOptionManagementInterface $attributeOptionManagement
     * @param AttributeRepositoryInterface $attributeRepository
     * @param AttributeOptionInterfaceFactory $optionDataFactory
     * @param StoreManagerInterface $storeManager
     * @param LabelToOption $mapper
     * @param DataObjectHelper $dataObjectHelper
     * @param string $entityType
     */
    public function __construct(
        AttributeOptionManagementInterface $attributeOptionManagement,
        AttributeRepositoryInterface $attributeRepository,
        AttributeOptionInterfaceFactory $optionDataFactory,
        StoreManagerInterface $storeManager,
        LabelToOption $mapper,
        DataObjectHelper $dataObjectHelper,
        $entityType
    ) {
        $this->attributeOptionManagement = $attributeOptionManagement;
        $this->storeManager = $storeManager;
        $this->optionDataFactory = $optionDataFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        parent::__construct($attributeRepository, $mapper, $entityType);
    }

    /**
     * Maps labels "Blue,Bluish Greenish,Green"
     * to option IDs "21,26,22"
     * And will dynamically create [option/value] [Blueish Greenish/26] if doesn't exist
     *
     * {@inheritdoc}
     */
    public function map($key, $value)
    {
        $this->updateOptionTable($key, $value);
        return parent::map($key, $value);
    }

    /**
     * Update attribute with any values found in comma separated $value argument
     *
     * @param $attributeCode
     * @param $inputValue
     */
    private function updateOptionTable($attributeCode, $inputValue)
    {
        $existingOptions = $this->attributeOptionManagement->getItems($this->entityType, $attributeCode);

        // Find new labels
        $labelsToAdd = $this->extractNewValues($existingOptions, $inputValue);

        // Get new option objects
        $maxSort = array_reduce($existingOptions, function ($currMax, $option) {
            /** @var AttributeOptionInterface $option */
            return $currMax > $option->getSortOrder() ? $currMax : $option->getSortOrder();
        }, 0);
        $optionsToAdd = $this->formatNewOptions($labelsToAdd, $maxSort);

        foreach($optionsToAdd as $option) {
            $this->attributeOptionManagement->add($this->entityType, $attributeCode, $option);
        }
    }

    /**
     * Returns array of input labels that dont exist yet
     *
     * @param AttributeOptionInterface[] $existingOptions
     * @param string $inputLabels
     * @return string[]
     */
    private function extractNewValues($existingOptions, $inputLabels)
    {
        $inputLabels = Escape::_explode($inputLabels);
        return array_filter($inputLabels, function($label) use ($existingOptions) {
            foreach($existingOptions as $existingOption) {
                if ($existingOption->getLabel() == $label) {
                    return false;
                }
            }
            return true;
        });
    }

    /**
     * Get new options in AttributeOptionInterface format
     *
     * @param $labelsToAdd
     * @param $currentMaxSort
     * @return AttributeOptionInterface[]
     */
    private function formatNewOptions($labelsToAdd, $currentMaxSort)
    {
        $newOptions = [];
        foreach($labelsToAdd as $label) {
            $optionObject = $this->optionDataFactory->create();
            $optionData = [
                'sort_order' => $currentMaxSort++,
                'label' => $label,
            ];
            $this->dataObjectHelper->populateWithArray(
                $optionObject,
                $optionData,
                '\Magento\Eav\Api\Data\AttributeOptionInterface'
            );
            $newOptions[] = $optionObject;
        }
        return $newOptions;
    }
}
