<?php
/**
 * @package     BlueAcorn\AmqpBase
 * @version     0.2.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\AmqpBase\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use BlueAcorn\AmqpBase\Helper\MessageQueue\Config as QueueConfig;
use BlueAcorn\AmqpBase\Model\Consumer\Daemonizer;

/**
 * Command for starting MessageQueue consumers.
 */
class ConsumerListCommand extends Command
{
    const COMMAND_QUEUE_CONSUMERS_LIST = 'queue:consumers:list';

    /**
     * @var QueueConfig
     */
    protected $queueConfig;

    /**
     * @var Daemonizer
     */
    protected $daemonizer;

    /**
     * @param QueueConfig $queueConfig
     * @param Daemonizer $daemonizer
     * @param string|null $name
     */
    public function __construct(
        QueueConfig $queueConfig,
        Daemonizer $daemonizer,
        $name = null
    ) {
        $this->queueConfig = $queueConfig;
        $this->daemonizer = $daemonizer;
        parent::__construct($name);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        foreach($this->queueConfig->getConsumersList() as $consumerName) {
            $output->writeln(sprintf('%30s: %d', $consumerName, $this->daemonizer->getCurrentDaemonCount($consumerName)));
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName(self::COMMAND_QUEUE_CONSUMERS_LIST);
        $this->setDescription('List of consumers');
        $this->setHelp(
            <<<HELP
This command shows list of consumers and the current number of daemon processes.
HELP
        );
        parent::configure();
    }
}
