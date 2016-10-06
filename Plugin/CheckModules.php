<?php
/**
 * @package     BlueAcorn\ModuleManager
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\ModuleManager\Plugin;

use BlueAcorn\ModuleManager\Helper\ModuleConfig;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\Config\ConfigOptionsListConstants;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;

class CheckModules
{
    /**
     * @var DeploymentConfig
     */
    private $deploymentConfig;

    /**
     * @var ModuleConfig
     */
    private $moduleConfig;

    /**
     * @param DeploymentConfig $deploymentConfig
     * @param ModuleConfig $moduleConfig
     */
    public function __construct(
        DeploymentConfig $deploymentConfig,
        ModuleConfig $moduleConfig
    ) {
        $this->deploymentConfig = $deploymentConfig;
        $this->moduleConfig = $moduleConfig;
    }

    /**
     * Throw exception before dispatching if configuration is out of sync.
     *
     * @see \Magento\Framework\Module\Plugin\DbStatusValidator
     * @param \Magento\Framework\App\FrontController $subject
     * @param \Closure $proceed
     * @param \Magento\Framework\App\RequestInterface $request
     * @return mixed
     * @throws LocalizedException
     */
    public function aroundDispatch(
        \Magento\Framework\App\FrontController $subject,
        \Closure $proceed,
        \Magento\Framework\App\RequestInterface $request
    ) {
        $this->checkConfigurationInSync();
        return $proceed($request);
    }

    /**
     * Checks that config.php reflects the same enable/disable data as modules.php
     *
     * @throws LocalizedException
     */
    protected function checkConfigurationInSync()
    {
        $customConfig = $this->moduleConfig->getModulesFromConfig();
        $defaultConfig = $this->deploymentConfig->get(ConfigOptionsListConstants::KEY_MODULES);
        // Equality check insures same key/value pairs, allows different order
        if ($customConfig != $defaultConfig) {
            $differingModules = $this->findDifferingKeys($customConfig, $defaultConfig);
            throw new LocalizedException(new Phrase(
                'Please upgrade your database: Run "bin/magento setup:upgrade" from the Magento root directory.'
                . '%1The following modules have invalid configuration:%2%3',
                [PHP_EOL, PHP_EOL, implode(PHP_EOL, $differingModules)]
            ));
        }
    }

    /**
     * Separate finding exact differences so that the normal performance hit
     * is just the equality check against arrays
     *
     * @param $firstList
     * @param $secondList
     * @return array
     */
    protected function findDifferingKeys($firstList, $secondList)
    {
        $differingKeys = [];
        $allKeys = array_merge(array_keys($firstList), array_keys($secondList));
        foreach($allKeys as $key) {
            if (!array_key_exists($key, $firstList) || !array_key_exists($key, $secondList)) {
                $differingKeys[] = $key;
                continue;
            }
            if ($firstList[$key] != $secondList[$key]) {
                $differingKeys[] = $key;
            }
        }
        return $differingKeys;
    }
}
