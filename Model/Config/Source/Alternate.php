<?php
/**
 * @package     BlueAcorn\ContentScheduler
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\ContentScheduler\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/** TODO: When Magento fixes CMS data implementations, revert commit to use interfaces/repositories/etc. */
abstract class Alternate implements OptionSourceInterface
{
    /**
     * Collection factory
     */
    protected $_collectionFactory;

    /**
     * Return array of ['value'=>$value, 'label'=>$label] pairs
     * Option to exclude certain entity ID (for `alternate` field)
     *
     * @param bool $withEmpty
     * @param null|string|int $excludeId
     * @return array
     * @throws Exception
     */
    public function toOptionArray($withEmpty = true, $excludeId = null)
    {
        $collection = $this->_collectionFactory->create();
        if (!$collection instanceof \Magento\Framework\Data\Collection\AbstractDb) {
            throw new Exception('Invalid collection factory.');
        }

        if ($excludeId) {
            $idFieldName = $collection->getIdFieldName();
            $collection->addFieldToFilter($idFieldName, ['neq' => $excludeId]);
        }

        $options = [];
        foreach($collection as $entity) {
            $options[] = ['value' => $entity->getId(), 'label' => $entity->getTitle()];
        }
        if ($withEmpty) {
            array_unshift($options, ['value' => '', 'label' => '-- Please Select --']);
        }

        return $options;
    }
}
