<?php
/**
 * @package     BlueAcorn\ModuleManager
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\ModuleManager\Console;

use BlueAcorn\ModuleManager\Helper\ModuleConfig;
use Magento\Framework\Config\ConfigOptionsListConstants;
use Magento\Framework\Config\File\ConfigFilePool;
use Magento\Framework\Module\ModuleList\Loader as ModuleListLoader;
use Magento\Framework\App\DeploymentConfig\Reader as DeploymentConfigReader;
use Magento\Framework\App\DeploymentConfig\Writer as DeploymentConfigWriter;
use Zend\ServiceManager\ServiceManager;
use Magento\Setup\Console\Command\AbstractSetupCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Override from core to update config.php before executing
 * @see Magento\Setup\Console\Command\UpgradeCommand
 */
class SetupUpgradeCommand extends AbstractSetupCommand
{
    /**
     * Option to skip deletion of var/generation directory
     */
    const INPUT_KEY_KEEP_GENERATED = 'keep-generated';

    /**
     * @var ModuleConfig
     */
    private $moduleConfig;

    /**
     * @var DeploymentConfigReader
     */
    private $deploymentConfigReader;

    /**
     * @var ModuleListLoader
     */
    private $moduleListLoader;

    /**
     * @var ServiceManager
     */
    private $serviceManager;

    /**
     * @var DeploymentConfigWriter
     */
    private $deploymentConfigWriter;

    public function __construct(
        ModuleConfig $moduleConfig,
        DeploymentConfigReader $deploymentConfigReader,
        DeploymentConfigWriter $deploymentConfigWriter,
        ModuleListLoader $moduleListLoader
    ) {
        parent::__construct();
        $this->moduleConfig = $moduleConfig;
        $this->deploymentConfigReader = $deploymentConfigReader;
        $this->deploymentConfigWriter = $deploymentConfigWriter;
        $this->moduleListLoader = $moduleListLoader;
        $this->serviceManager = \Zend\Mvc\Application::init(require BP . '/setup/config/application.config.php')
            ->getServiceManager();
    }

    /**
     * First update config.php with module.php values
     * Then issue original command
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->updateDeploymentConfig();

        // Original command is overwitten since they share the same name
        /** @var \Magento\Setup\Console\Command\UpgradeCommand $originalCommand */
        $originalCommand = $this->serviceManager->create('Magento\Setup\Console\Command\UpgradeCommand');

        // The command::run method parses $input/$output,
        // this results in bugs not allowing shortcuts, so we need to reference the protected execute method
        $reflection = new \ReflectionClass(get_class($originalCommand));
        $closure = $reflection->getMethod('execute')->getClosure($originalCommand);
        $closure($input, $output);

    }

    /**
     * Update config.php with logic from \Magento\Setup\Model\Installer::createModulesConfigRewrite
     * Uses values from modules.php file
     *
     * @throws \Exception
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function updateDeploymentConfig()
    {
        $allModules = array_keys($this->moduleListLoader->load());
        $deploymentConfig = $this->deploymentConfigReader->load();
        $defaultConfigModules = isset($deploymentConfig[ConfigOptionsListConstants::KEY_MODULES])
            ? $deploymentConfig[ConfigOptionsListConstants::KEY_MODULES]
            : [];
        $customConfigModules = $this->moduleConfig->getModulesFromConfig();
        $currentModules = array_merge($defaultConfigModules, $customConfigModules);
        $result = [];
        foreach($allModules as $module) {
            $result[$module] = (isset($currentModules[$module]) && !$currentModules[$module]) ? 0 : 1;
        }
        $this->deploymentConfigWriter->saveConfig([ConfigFilePool::APP_CONFIG => ['modules' => $result]], true);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $options = [
            new InputOption(
                self::INPUT_KEY_KEEP_GENERATED,
                null,
                InputOption::VALUE_NONE,
                'Prevents generated code from being deleted. ' . PHP_EOL .
                'We discourage using this option except when deploying to production. ' . PHP_EOL .
                'Consult your system integrator or administrator for more information.'
            )
        ];
        $this->setName('setup:upgrade')
            ->setDescription('Upgrades the Magento application, DB data, and schema')
            ->setDefinition($options);
        parent::configure();
    }
}
