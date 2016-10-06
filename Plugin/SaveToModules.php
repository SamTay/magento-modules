<?php
/**
 * @package     BlueAcorn\ModuleManager
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\ModuleManager\Plugin;

use BlueAcorn\ModuleManager\Helper\ModuleConfig as CustomModuleConfig;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\DeploymentConfig;

class SaveToModules
{
    /**
     * @var CustomModuleConfig
     */
    private $moduleConfig;

    /**
     * @param CustomModuleConfig $moduleConfig
     */
    public function __construct(CustomModuleConfig $moduleConfig)
    {
        $this->moduleConfig = $moduleConfig;
    }

    /**
     * Write to modules.php after config.php
     *
     * @param DeploymentConfig\Writer $subject
     * @param $result
     */
    public function afterSaveConfig(DeploymentConfig\Writer $subject, $result)
    {
        $this->moduleConfig->saveModulesToConfig();
    }
}
