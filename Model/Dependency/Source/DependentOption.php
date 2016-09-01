<?php
/**
 * @package     BlueAcorn\LayeredNavigation
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\LayeredNavigation\Model\Dependency\Source;

use Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection as AttributeCollection;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory;
use Magento\Framework\Data\OptionSourceInterface;

class DependentOption implements OptionSourceInterface
{
    /** @var FilterAttribute */
    private $filterAttributeSource;

    /** @var CollectionFactory */
    private $collectionFactory;

    /** @var array */
    protected $options;

    /**
     * DependentOption constructor.
     * @param FilterAttribute $filterAttributeSource
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        FilterAttribute $filterAttributeSource,
        CollectionFactory $collectionFactory
    ) {
        $this->filterAttributeSource = $filterAttributeSource;
        $this->collectionFactory = $collectionFactory;
    }

    public function toOptionArray($excludeId = null)
    {
        if (is_null($this->options)) {
            $attributeCollection = $this->filterAttributeSource->getList();
            $attributeIds = $attributeCollection->getAllIds();
            $optionCollection = $this->collectionFactory->create()
                ->addFieldToFilter('attribute_id', ['in' => [$attributeIds]])
                ->setStoreFilter();
            foreach($optionCollection as $option) {
                $optionLabel = $option->getValue();
                $attributeLabel = $attributeCollection->getItemById($option->getAttributeId())
                    ->getDefaultFrontendLabel();
                $this->options[] = [
                    'value' => $option->getOptionId(),
                    'label' => "$attributeLabel -- $optionLabel",
                    'attribute_id' => $option->getAttributeId()
                ];
            }
            usort($this->options, function($a, $b) {
                return strcmp($a['label'], $b['label']);
            });
        }
        if ($excludeId) {
            return array_filter($this->options, function($option) use($excludeId) {
                return $option['attribute_id'] != $excludeId;
            });
        }
        return $this->options;
    }
}
