<?php
/**
 * @package     BlueAcorn\LayeredNavigation
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\LayeredNavigation\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Dependency extends AbstractDb
{
    /**
     * Set main table and id field name
     */
    protected function _construct()
    {
        $this->_init('ba_layerednav_filter_dependency', 'dependency_id');
    }
}
