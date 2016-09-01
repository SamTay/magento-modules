<?php
/**
 * @package     BlueAcorn\LayeredNavigation
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\LayeredNavigation\Api\Data;

interface DependencyInterface
{
    const DEPENDENCY_ID = 'dependency_id';
    const FILTER_ATTRIBUTE = 'attribute';
    const FILTER_ATTRIBUTE_ID = 'attribute_id';
    const OPTION_ID = 'option_id';
    const STORE_ID = 'store_id';

    /**
     * @return int
     */
    public function getId();

    /**
     * @param int $id
     * @return $this
     */
    public function setId($id);

    /**
     * @return \Magento\Catalog\Model\ResourceModel\Eav\Attribute
     */
    public function getFilterAttribute();

    /**
     * @param \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute
     * @return $this
     */
    public function setFilterAttribute($attribute);

    /**
     * @return int
     */
    public function getFilterAttributeId();

    /**
     * @param int $id
     * @return $this
     */
    public function setFilterAttributeId($id);

    /**
     * @return int
     */
    public function getOptionId();

    /**
     * @param int $id
     * @return $this
     */
    public function setOptionId($id);
}
