<?php
/**
 * @package     BlueAcorn\ContentPublisher
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\ContentPublisher\Model\Entity\Attribute\Backend;

/**
 * Class Startdate
 *
 * Override to remove special price dependency (still incorrect from M1)
 */
class Startdate extends \Magento\Catalog\Model\Attribute\Backend\Startdate
{
    /**
     * Override to remove special price dependency
     * @param \Magento\Framework\DataObject $object
     * @return mixed
     */
    public function _getValueForSave($object)
    {
        $attributeName = $this->getAttribute()->getName();
        return $object->getData($attributeName);
    }
}
