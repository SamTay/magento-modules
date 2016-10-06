<?php
/**
 * @package     BlueAcorn\ModuleManager
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\ModuleManager\Plugin;

use BlueAcorn\ModuleManager\Helper\ModuleConfig as CustomModuleConfig;
use Magento\Framework\App\DeploymentConfig\Reader;
use Magento\Framework\Config\ConfigOptionsListConstants;

class ReadFromModules
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
     * Merge enable/disable data from modules.php after config.php
     *
     * @param Reader $subject
     * @param $result
     */
    public function afterLoad(Reader $subject, $result)
    {
        // Only modify when modules are loaded into deployment config
        if (!isset($result[ConfigOptionsListConstants::KEY_MODULES])) {
            return $result;
        }

        $sortedModules = $result[ConfigOptionsListConstants::KEY_MODULES];
        $modules = $this->moduleConfig->getModulesFromConfig();

        // Override {0,1} enable,disable flag with data from modules.php
        // Using foreach because I am scared of array functions changing sequence sorting
        foreach($sortedModules as $moduleName => &$isEnabled) {
            if (array_key_exists($moduleName, $modules)) {
                $isEnabled = $modules[$moduleName];
            }
        }
        $result[ConfigOptionsListConstants::KEY_MODULES] = $sortedModules;
        return $result;
    }
}
