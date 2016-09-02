<?php
/**
 * @package     BlueAcorn\LayeredNavigation
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\LayeredNavigation\Model\ResourceModel;

use Magento\Catalog\Model\Layer\FilterableAttributeListInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;
use BlueAcorn\LayeredNavigation\Model\Dependency as DependencyModel;

class Dependency extends AbstractDb
{
    /** @var FilterableAttributeListInterface */
    protected $filterableAttributeList;

    /**
     * Dependency constructor.
     * @param FilterableAttributeListInterface $filterableAttributeList
     * @param Context $context
     * @param $connectionName
     */
    public function __construct(
        FilterableAttributeListInterface $filterableAttributeList,
        Context $context,
        $connectionName = null
    ) {
        parent::__construct($context, $connectionName);
        $this->filterableAttributeList = $filterableAttributeList;
    }

    /**
     * Set main table and id field name
     */
    protected function _construct()
    {
        $this->_init('ba_layerednav_filter_dependency', 'dependency_id');
    }

    /**
     * Initialize unique fields
     *
     * @return $this
     */
    protected function _initUniqueFields()
    {
        $this->_uniqueFields = [
            ['field' => ['attribute_id', 'option_id'], 'title' => __('Dependency already exists')],
        ];
        return $this;
    }

    /**
     * Load attribute model onto dependency model
     *
     * @param DependencyModel $dependency
     * @throws \InvalidArgumentException
     * @return $this
     */
    public function loadAttributeData(DependencyModel $dependency)
    {
        $attributeId = $dependency->getFilterAttributeId();
        if ($attributeId) {
            $attribute = $this->filterableAttributeList->getList()
                ->addFieldToFilter('main_table.attribute_id', $attributeId)
                ->getFirstItem();
            if ($attribute->getId()) {
                $dependency->setFilterAttribute($attribute);
            } else {
                throw new \InvalidArgumentException('Attribute ID either does not exist or is not filterable');
            }
        }

        return $this;
    }
}
