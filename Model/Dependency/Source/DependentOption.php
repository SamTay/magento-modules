<?php
/**
 * @package     BlueAcorn\LayeredNavigation
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\LayeredNavigation\Model\Dependency\Source;

use Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory;

class DependentOption extends AbstractSource
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

    /**
     * Get option array of available eav_attribute_option's
     * Optionally exclude options from one attribute id
     *
     * @param null $excludeId
     * @return array
     */
    public function toOptionArray($excludeId = null)
    {
        if (is_null($this->options)) {
            $attributes = $this->filterAttributeSource->toOptionArray();
            $attributeIds = array_map(function($attr) {return $attr['value'];}, $attributes);
            $optionCollection = $this->collectionFactory->create()
                ->addFieldToFilter('attribute_id', ['in' => [$attributeIds]])
                ->setStoreFilter();
            foreach($optionCollection as $option) {
                $optionLabel = $option->getValue();
                $attributeLabel = $this->filterAttributeSource->getLabelByValue($option->getAttributeId());
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
