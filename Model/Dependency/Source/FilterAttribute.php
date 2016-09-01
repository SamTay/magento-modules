<?php
/**
 * @package     BlueAcorn\LayeredNavigation
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\LayeredNavigation\Model\Dependency\Source;

use Magento\Catalog\Model\Layer\FilterableAttributeListInterface;

class FilterAttribute extends AbstractSource
{
    /** @var FilterableAttributeListInterface */
    private $filterAttributeList;

    /** @var array|null */
    protected $options;

    /**
     * FilterAttribute constructor.
     * @param FilterableAttributeListInterface $filterAttributeList
     */
    public function __construct(
        FilterableAttributeListInterface $filterAttributeList
    ) {
        $this->filterAttributeList = $filterAttributeList;
    }

    /**
     * Get option array of filterable attributes
     *
     * @return array
     */
    public function toOptionArray()
    {
        // Why don't any native collections properly assign arguments to _toOptionArray? Sigh..
        if (is_null($this->options)) {
            $this->options = [];
            foreach($this->filterAttributeList->getList() as $attribute) {
                /** @var $attribute \Magento\Catalog\Model\ResourceModel\Eav\Attribute */
                $this->options[] = [
                    'value' => $attribute->getAttributeId(),
                    'label' => $attribute->getDefaultFrontendLabel()
                ];
            }
        }
        return $this->options;
    }
}
