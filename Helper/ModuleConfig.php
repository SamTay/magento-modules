<?php
/**
 * @package     BlueAcorn\ModuleManager
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\ModuleManager\Helper;

use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\DeploymentConfig\Reader;
use Magento\Framework\Config\ConfigOptionsListConstants;
use Magento\Framework\Filesystem\DriverPool;
use Magento\Framework\Filesystem;
use Magento\Framework\App\DeploymentConfig\Writer\PhpFormatter;

class ModuleConfig
{
    const FILENAME = 'modules.php';

    /**
     * @var DirectoryList
     */
    private $dirList;

    /**
     * @var DriverPool
     */
    private $driverPool;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var PhpFormatter
     */
    private $phpFormatter;

    /**
     * @var DeploymentConfig
     */
    private $deploymentConfig;

    /**
     * List of modules from modules.php
     * ['ModuleName' => (bool)$isEnabled]
     * @var array
     */
    private $modules;

    public function __construct(
        DeploymentConfig $deploymentConfig,
        DirectoryList $dirList,
        DriverPool $driverPool,
        Filesystem $filesystem,
        PhpFormatter $phpFormatter
    ) {
        $this->dirList = $dirList;
        $this->driverPool = $driverPool;
        $this->deploymentConfig = $deploymentConfig;
        $this->filesystem = $filesystem;
        $this->phpFormatter = $phpFormatter;
    }

    /**
     * Get modules from modules.php
     *
     * @return array
     */
    public function getModulesFromConfig()
    {
        if (!$this->modules) {
            $filePath = $this->dirList->getPath(DirectoryList::CONFIG) . DIRECTORY_SEPARATOR . self::FILENAME;
            $fileDriver = $this->driverPool->getDriver(DriverPool::FILE);
            if (!$fileDriver->isExists($filePath)) {
                $this->modules = [];
            } else {
                $this->modules = include $filePath;
            }
        }
        return $this->modules;
    }

    /**
     * Save modules to modules.php. If no array passed, uses deployment config data
     *
     * @param array $modules
     */
    public function saveModulesToConfig($modules = [])
    {
        if (!$modules) {
            $modules = $this->deploymentConfig->getConfigData(ConfigOptionsListConstants::KEY_MODULES);
        }
        ksort($modules);
        $contents = $this->phpFormatter->format($modules);
        $this->filesystem->getDirectoryWrite(DirectoryList::CONFIG)->writeFile(self::FILENAME, $contents);

        $this->modules = null;
    }
}
