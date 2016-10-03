<?php
/**
 * @package     BlueAcorn\LayeredNavigation
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\LayeredNavigation\Api;

use Magento\Catalog\Model\Layer;

interface DependencyManagerInterface
{
    /**
     * Get array of attribtue IDs with unmet dependencies for the current Layer
     *
     * @param Layer $layer
     * @return int[]
     */
    public function getUnmetDependencies(Layer $layer);
}
