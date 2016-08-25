<?php
/**
 * @package     BlueAcorn\LayeredNavigation
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\LayeredNavigation\Model\ResourceModel\Dependency;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * Initialize with model/resource class names
     */
    protected function _construct()
    {
        $this->_init(
            'BlueAcorn\LayeredNavigation\Model\Dependency',
            'BlueAcorn\LayeredNavigation\Model\ResourceModel\Dependency'
        );
    }
}
