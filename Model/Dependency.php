<?php
/**
 * @package     BlueAcorn\LayeredNavigation
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\LayeredNavigation\Model;

use Magento\Framework\Model\AbstractModel;

class Dependency extends AbstractModel
{
    /**
     * Initialize with resource name
     */
    protected function _construct()
    {
        $this->_init('BlueAcorn\LayeredNavigation\Model\ResourceModel\Dependency');
    }
}
