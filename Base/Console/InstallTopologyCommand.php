<?php
/**
 * @package     BlueAcorn\AmqpBase
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\AmqpBase\Console;

use BlueAcorn\AmqpBase\Model\Topology;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command for starting installing rabbitmq topology
 */
class InstallTopologyCommand extends Command
{
    const COMMAND_QUEUE_TOPOLOGY_INSTALL = 'queue:topology:install';

    /**
     * @var Topology
     */
    private $topology;

    /**
     * @param Topology $topology
     * @param string|null $name
     */
    public function __construct(Topology $topology, $name = null)
    {
        $this->topology = $topology;
        parent::__construct($name);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->topology->install();
        $output->writeln('<info>Topology installation complete. Check the logs for troubleshooting.</info>');
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName(self::COMMAND_QUEUE_TOPOLOGY_INSTALL);
        $this->setDescription('Installation of rabbitMQ topology');
        $this->setHelp(
            <<<HELP
This command takes the AMQP topology configured by app/etc/env.php and all modules' merged etc/queue.xml files,

and sets up the rabbitMQ topology (installs exchanges, queues, and bind relations).
HELP
        );
        parent::configure();
    }
}
