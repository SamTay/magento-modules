<?php
/**
 * @package     BlueAcorn\AmqpBase
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\AmqpBase\Model\Consumer;

use BlueAcorn\AmqpBase\Model\Consumer;
use BlueAcorn\AmqpBase\Model\Shell\Parallelizer;
use BlueAcorn\AmqpBase\Model\Topology;
use BlueAcorn\AmqpBase\Helper\Consumer\Config as ConsumerConfig;
use BlueAcorn\AmqpBase\Console\StartConsumerCommand;
use Magento\Framework\Exception\LocalizedException;
use BlueAcorn\AmqpBase\Helper\MessageQueue\Config as QueueConfig;
use Magento\Framework\Json\Encoder as JsonEncoder;
use Magento\Framework\MessageQueue\PublisherFactory;
use Magento\Framework\Phrase;

class Daemonizer
{
    const MAX_DAEMON_COUNT = 20;

    /** Statuses for updating configuration & starting consumers */
    const STATUS_NO_ACTION_NECESSARY = 0;
    const STATUS_TRUNCATE_NECESSARY = 1;
    const STATUS_SPAWN_NECESSARY = 2;

    /**
     * @var Topology
     */
    protected $topology;

    /**
     * @var QueueConfig
     */
    protected $queueConfig;

    /**
     * @var ConsumerConfig
     */
    protected $consumerConfig;

    /**
     * @var PublisherFactory
     */
    protected $publisherFactory;

    /**
     * @var Parallelizer
     */
    protected $shell;

    /**
     * @var JsonEncoder
     */
    protected $jsonEncoder;

    /**
     * Daemonizer constructor.
     * @param Topology $topology
     * @param QueueConfig $queueConfig
     * @param ConsumerConfig $consumerConfig
     * @param PublisherFactory $publisherFactory
     * @param Parallelizer $shell
     * @param JsonEncoder $jsonEncoder
     */
    public function __construct(
        Topology $topology,
        QueueConfig $queueConfig,
        ConsumerConfig $consumerConfig,
        PublisherFactory $publisherFactory,
        Parallelizer $shell,
        JsonEncoder $jsonEncoder
    ) {
        $this->topology = $topology;
        $this->queueConfig = $queueConfig;
        $this->consumerConfig = $consumerConfig;
        $this->publisherFactory = $publisherFactory;
        $this->shell = $shell;
        $this->jsonEncoder = $jsonEncoder;
    }

    /**
     * Start all consumers according to configured daemon counts
     * Optionally disallow truncating daemons
     * Optionally dry run without modifying daemon counts
     *
     * Returns status array: [
     * SPAWN_NECESSARY =>
     *   [
     *     $consumer1 => $diffCount1,
     *     $consumer2 => $diffCount2
     *   ],
     * TRUNCATE_NECESSARY => [
     * ....
     *
     * @param bool $noTruncate
     * @param bool $dryRun
     * @return array
     * @throws LocalizedException
     */
    public function startAllConsumers($noTruncate = false, $dryRun = false)
    {
        $statuses = [
            self::STATUS_NO_ACTION_NECESSARY => [],
            self::STATUS_SPAWN_NECESSARY => [],
            self::STATUS_TRUNCATE_NECESSARY => []
        ];
        foreach($this->queueConfig->getConsumersList() as $consumerName) {
            $configuredDaemonCount = $this->consumerConfig->getDaemonCount($consumerName);
            $currentDaemonCount = $this->getCurrentDaemonCount($consumerName);

            $diff = $configuredDaemonCount - $currentDaemonCount;
            switch(true) {
                case ($diff > 0):
                    $dryRun || $this->addDaemons($consumerName, $diff);
                    $statuses[self::STATUS_SPAWN_NECESSARY][$consumerName] = $diff;
                    break;
                case ($diff < 0):
                    $dryRun || $noTruncate || $this->removeDaemons($consumerName, abs($diff));
                    $statuses[self::STATUS_TRUNCATE_NECESSARY][$consumerName] = abs($diff);
                    break;
                default:
                    $statuses[self::STATUS_NO_ACTION_NECESSARY][$consumerName] = 0;
                    break;
            }
        }

        return $statuses;
    }

    /**
     * Check current status between Magento configuration and rabbitMQ processes
     * Delegates to starting consumers by dry run
     *
     * @return array
     */
    public function checkStatus()
    {
        return $this->startAllConsumers(false, true);
    }

    /**
     * Adds extra daemons for a consumer
     *
     * @param $consumerName
     * @param $count
     * @throws LocalizedException
     */
    public function addDaemons($consumerName, $count)
    {
        $count = (int)$count >= 0 ? (int)$count : 0;
        if (!$this->canCreateDaemons($consumerName, $count)) {
            throw new LocalizedException(new Phrase('Requested daemon count exceeds maximum limit'));
        }
        for($i=0; $i<$count; $i++) {
            $this->spawnConsumer($consumerName);
        }
    }

    /**
     * Removes daemons for a consumer
     *
     * @param $consumerName
     * @param $count
     */
    public function removeDaemons($consumerName, $count)
    {
        $count = min((int)$count, $this->getCurrentDaemonCount($consumerName));
        for($i=0; $i<$count; $i++) {
            $this->truncateConsumer($consumerName);
        }
    }

    /**
     * Get current daemon count for consumer
     * TODO: Investigate passive queue declaration to check for existence. May or may not close connection
     * on IO exception, but could be a cleaner solution than this
     *
     * @param $consumerName
     * @return int
     * @throws LocalizedException
     */
    public function getCurrentDaemonCount($consumerName)
    {
        $queueName = $this->queueConfig->getQueueByConsumer($consumerName);
        /**
         * I don't want to extend topology class and I don't want the methods to be public.
         * It is a very particular use case that the declaration returns a count of consumers, so I am
         * explicitly breaking OOO to leverage that return value.
         */
        $getConsumerCount = function($queueName) {
            list(,,$consumerCount) = $this->declareQueue($queueName);
            return $consumerCount;
        };
        $declareAccessor = \Closure::bind($getConsumerCount, $this->topology, $this->topology);
        return $declareAccessor($queueName);
    }

    /**
     * Check if additional daemon count will exceed maximum
     *
     * @param $consumerName
     * @param $additional
     * @return false
     */
    public function canCreateDaemons($consumerName, $additional)
    {
        return $additional <= $this->getMaximumAdditionalDaemonCount($consumerName);
    }

    /**
     * Get the maximum number of additional daemons possible (limited by constant MAX_DAEMON_COUNT)
     *
     * @param $consumerName
     * @return int
     */
    public function getMaximumAdditionalDaemonCount($consumerName)
    {
        return self::MAX_DAEMON_COUNT - $this->getCurrentDaemonCount($consumerName);
    }

    /**
     * Truncates consumer daemon count (i.e., removes a single consumer daemon)
     *
     * @param $consumerName
     * @throws LocalizedException
     */
    protected function truncateConsumer($consumerName)
    {
        $topic = $this->queueConfig->getTopicFromConsumer($consumerName);
        $publisher = $this->publisherFactory->create($topic);
        $publisher->publish($topic, $this->jsonEncoder->encode([Consumer::SHUTDOWN_PROTOCOL => true]));
    }

    /**
     * Spawns consumer in a parallel background process
     *
     * @param $consumerName
     * @throws LocalizedException
     */
    protected function spawnConsumer($consumerName)
    {
        $this->shell->execute(
            'php %s %s %s --%s=%s=1',
            [
                BP . '/bin/magento',
                StartConsumerCommand::COMMAND_QUEUE_CONSUMERS_START,
                $consumerName,
                StartConsumerCommand::OPTION_INTERNAL_PARAMS,
                StartConsumerCommand::STANDALONE_PROCESS_FLAG
            ]
        );
    }
}
