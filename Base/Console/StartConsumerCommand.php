<?php
/**
 * @package     BlueAcorn\AmqpBase
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\AmqpBase\Console;

use Magento\Framework\Console\Cli;
use Magento\Framework\Shell\ComplexParameter;
use Magento\Framework\ShellInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\MessageQueue\ConsumerFactory;

/**
 * Command for starting MessageQueue consumers.
 *
 * Rewritten from module-message-queue to allow passing daemon count
 */
class StartConsumerCommand extends Command
{
    const ARGUMENT_CONSUMER = 'consumer';
    const OPTION_NUMBER_OF_MESSAGES = 'max-messages';
    const OPTION_DAEMON_COUNT = 'daemon-count';
    const OPTION_INTERNAL_PARAMS = 'internal-params';
    const STANDALONE_PROCESS_FLAG = 'standalone-process-flag';
    const COMMAND_QUEUE_CONSUMERS_START = 'queue:consumers:start';

    /**
     * @var ConsumerFactory
     */
    protected $consumerFactory;

    /**
     * @var ShellInterface
     */
    protected $shell;

    /**
     * {@inheritdoc}
     *
     * @param ConsumerFactory $consumerFactory
     * @param ShellInterface $shell
     */
    public function __construct(
        ConsumerFactory $consumerFactory,
        ShellInterface $shell,
        $name = null
    ) {
        $this->consumerFactory = $consumerFactory;
        $this->shell = $shell;
        parent::__construct($name);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $consumerName = $input->getArgument(self::ARGUMENT_CONSUMER);
        $daemonCount = (int)$input->getOption(self::OPTION_DAEMON_COUNT);
        $numberOfMessages = $daemonCount ? 0 : (int)$input->getOption(self::OPTION_NUMBER_OF_MESSAGES);
        $standaloneProcessFlag = $this->isStandaloneProcess($input);
        $consumer = $this->consumerFactory->get($consumerName);

        // If standalone flag (i.e. started from daemon loop below), start off consumer daemon
        if ($standaloneProcessFlag) {
            $consumer->process();
            return;
        }

        // If running a fixed, terminating consumer, process limited messages and return
        if ($numberOfMessages) {
            $consumer->process($numberOfMessages);
            $output->writeln('Started consumer that will terminate after ' . $numberOfMessages . ' messages.');
            return;
        }

        // Default to 1 daemon on empty/invalid input
        $daemonCount = $daemonCount >= 1 ? $daemonCount : 1;
        for($i=0; $i<$daemonCount; $i++) {
            $this->shell->execute(
                'php %s %s %s --%s=%s=1',
                [
                    BP . '/bin/magento',
                    self::COMMAND_QUEUE_CONSUMERS_START,
                    $consumerName,
                    self::OPTION_INTERNAL_PARAMS,
                    self::STANDALONE_PROCESS_FLAG
                ]
            );
        }
        $output->writeln('Started ' . $daemonCount . ' daemon consumer instances.');
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName(self::COMMAND_QUEUE_CONSUMERS_START);
        $this->setDescription('Start MessageQueue consumer');
        $this->addArgument(
            self::ARGUMENT_CONSUMER,
            InputArgument::REQUIRED,
            'The name of the consumer to be started.'
        );
        $this->addOption(
            self::OPTION_DAEMON_COUNT,
            'd',
            InputOption::VALUE_REQUIRED,
            'The number of daemon processes to start. If not specified, defaults to a single process.'
        );
        $this->addOption(
            self::OPTION_NUMBER_OF_MESSAGES,
            null,
            InputOption::VALUE_REQUIRED,
            'The number of messages to be processed by the consumer before process termination. '
            . 'If not specified - start a single daemon process. (Ignored when daemon count is specified)'
        );
        $this->addOption(
            self::OPTION_INTERNAL_PARAMS,
            null,
            InputOption::VALUE_REQUIRED,
            'Flags for internal command execution, please leave empty.'
        );
        $this->setHelp(
            <<<HELP
This command starts MessageQueue consumer by its name.

To run the consumer as a single daemon instance:

      <comment>%command.full_name% someConsumer</comment>

To run the consumer as N separate daemon instances:

      <comment>%command.full_name% someConsumer --daemon-count=N</comment>

To run a consumer that terminates after consuming N messages:

    <comment>%command.full_name% someConsumer --max-messages=N</comment>
HELP
        );
        parent::configure();
    }

    /**
     * Check if standalone process flag is set
     *
     * @param InputInterface $input
     * @return bool
     */
    protected function isStandaloneProcess(InputInterface $input)
    {
        $internalParams = $input->getOption(self::OPTION_INTERNAL_PARAMS);
        if (!$internalParams) {
            return false;
        }
        $processor = new ComplexParameter(self::OPTION_INTERNAL_PARAMS);
        $optionValues = $processor->getFromString('--' . self::OPTION_INTERNAL_PARAMS . '=' . $internalParams);
        return isset($optionValues[self::STANDALONE_PROCESS_FLAG]) && $optionValues[self::STANDALONE_PROCESS_FLAG];
    }
}
