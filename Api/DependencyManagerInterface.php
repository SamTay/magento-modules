<?php
/**
 * @package     BlueAcorn\LayeredNavigation
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\LayeredNavigation\Api;

use BlueAcorn\LayeredNavigation\Api\Data\DependencyInterface;
use Magento\Catalog\Model\Layer\State;

interface DependencyManagerInterface
{
    /**
     * Get array of attribtue IDs with unmet dependencies
     *
     * @param State $state
     * @return int[]
     */
    public function getUnmetDependencies(State $state);
}
