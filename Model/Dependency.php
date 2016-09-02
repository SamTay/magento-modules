<?php
/**
 * @package     BlueAcorn\LayeredNavigation
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\LayeredNavigation\Model;

use BlueAcorn\LayeredNavigation\Api\Data\DependencyInterface;
use Magento\Framework\Model\AbstractModel;

class Dependency extends AbstractModel implements DependencyInterface
{
    /** @var string */
    protected $_eventPrefix = 'ba_layerednav_dependency';

    /** @var string */
    protected $_eventObject = 'dependency';

    /**
     * Initialize with resource name
     */
    protected function _construct()
    {
        $this->_init('BlueAcorn\LayeredNavigation\Model\ResourceModel\Dependency');
    }

    /**
     * @return \Magento\Catalog\Model\ResourceModel\Eav\Attribute
     */
    public function getFilterAttribute()
    {
        if (!$this->getData(self::FILTER_ATTRIBUTE) && $this->getData(self::FILTER_ATTRIBUTE_ID)) {
            $this->getResource()->loadAttributeData($this);
        }
        return $this->getData(self::FILTER_ATTRIBUTE);
    }

    /**
     * @param \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute
     * @return $this
     */
    public function setFilterAttribute($attribute)
    {
        return $this->setData(self::FILTER_ATTRIBUTE, $attribute);
    }

    /**
     * @return int
     */
    public function getFilterAttributeId()
    {
        return $this->getData(self::FILTER_ATTRIBUTE_ID);
    }

    /**
     * @param int $id
     * @return $this
     */
    public function setFilterAttributeId($id)
    {
        return $this->setData(self::FILTER_ATTRIBUTE_ID, $id);
    }

    /**
     * @return int
     */
    public function getOptionId()
    {
        return $this->getData(self::OPTION_ID);
    }

    /**
     * @param int $id
     * @return $this
     */
    public function setOptionId($id)
    {
        return $this->setData(self::OPTION_ID, $id);
    }

    /**
     * Receive page store ids
     *
     * @return int[]
     */
    public function getStores()
    {
        return $this->hasData('stores') ? $this->getData('stores') : $this->getData('store_id');
    }
}
