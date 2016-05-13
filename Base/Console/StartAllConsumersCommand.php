<?php
/**
 * @package     BlueAcorn\AmqpBase
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\AmqpBase\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use BlueAcorn\AmqpBase\Model\Consumer\Daemonizer;

/**
 * Command for starting MessageQueue consumers.
 *
 * This command accepts no arguments, and automatically sets daemon counts to their configured values
 */
class StartAllConsumersCommand extends Command
{
    const OPTION_NO_TRUNCATE = 'no-truncate';
    const OPTION_DRY_RUN = 'dry-run';
    const COMMAND_QUEUE_CONSUMERS_START_ALL = 'queue:consumers:start-all';

    /**
     * @var Daemonizer
     */
    protected $daemonizer;

    /**
     * {@inheritdoc}
     * @param Daemonizer $daemonizer
     */
    public function __construct(
        Daemonizer $daemonizer,
        $name = null
    ) {
        $this->daemonizer = $daemonizer;
        parent::__construct($name);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $noTruncateFlag = $input->getOption(self::OPTION_NO_TRUNCATE);
        $dryRun = $input->getOption(self::OPTION_DRY_RUN);
        $statuses = $this->daemonizer->startAllConsumers($noTruncateFlag, $dryRun);
        if (!$dryRun) {
            $output->writeln(
                '<info>'
                . 'Started consumers according to system configuration'
                . '</info>'
            );
        } else {
            $map = $this->getStatusMap();
            foreach ($statuses as $status => $consumers) {
                foreach($consumers as $consumerName => $diff) {
                    $output->writeln(sprintf('%30s: ' . $map[$status], $consumerName, $diff));
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName(self::COMMAND_QUEUE_CONSUMERS_START_ALL);
        $this->setDescription('Start all consumer daemons as specified by configuration');
        $this->addOption(
            self::OPTION_NO_TRUNCATE,
            null,
            InputOption::VALUE_NONE,
            'Optionally disallow truncating; will only add consumers and do not remove them.'
        );
        $this->addOption(
            self::OPTION_DRY_RUN,
            null,
            InputOption::VALUE_NONE,
            'Just dry run and inform what this command would do.'
        );
        $this->setHelp(
            <<<HELP
This command starts all consumers to the daemon count specified in system configuration. If the
configured daemon count is lower than the current daemon count for a particular consumer, passing the --no-truncate
option will ensure that daemon count does not decrease.

To start up or maintain the configured consumer daemons:

      <comment>%command.full_name%</comment>
HELP
        );
        parent::configure();
    }

    /**
     * Get human readable text for each status type
     *
     * @return array
     */
    protected function getStatusMap()
    {
        return [
            Daemonizer::STATUS_SPAWN_NECESSARY => 'spawn %d daemons',
            Daemonizer::STATUS_TRUNCATE_NECESSARY => 'truncate %d daemons',
            Daemonizer::STATUS_NO_ACTION_NECESSARY => 'no action necessary'
        ];
    }
}
