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
use Magento\Framework\MessageQueue\Config\Data as QueueConfig;
use Magento\Framework\MessageQueue\Config\Converter as QueueConverter;
use Magento\Framework\MessageQueue\PublisherFactory;
use Magento\Framework\Phrase;

class Daemonizer
{
    const MAX_DAEMON_COUNT = 20;

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
     * Daemonizer constructor.
     * @param Topology $topology
     * @param QueueConfig $queueConfig
     * @param ConsumerConfig $consumerConfig
     * @param PublisherFactory $publisherFactory
     * @param Parallelizer $shell
     */
    public function __construct(
        Topology $topology,
        QueueConfig $queueConfig,
        ConsumerConfig $consumerConfig,
        PublisherFactory $publisherFactory,
        Parallelizer $shell
    ) {
        $this->topology = $topology;
        $this->queueConfig = $queueConfig;
        $this->consumerConfig = $consumerConfig;
        $this->publisherFactory = $publisherFactory;
        $this->shell = $shell;
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
     *
     * @param $consumerName
     * @return int
     * @throws LocalizedException
     */
    public function getCurrentDaemonCount($consumerName)
    {
        $queueName = $this->getQueueByConsumer($consumerName);
        return $this->topology->getConsumerCount($queueName);
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
     * Get queue name from consumer name
     *
     * @param string $consumerName
     * @return string
     * @throws LocalizedException
     */
    public function getQueueByConsumer($consumerName)
    {
        $path = implode('/', [
            QueueConverter::CONSUMERS,
            $consumerName,
            QueueConverter::CONSUMER_QUEUE
        ]);
        $queueName = $this->queueConfig->get($path);
        if (!$queueName) {
            throw new LocalizedException(
                new Phrase('No queue specified for consumer %name', ['name' => $consumerName])
            );
        }

        return $queueName;
    }

    /**
     * Truncates consumer daemon count (i.e., removes a single consumer daemon)
     *
     * @param $consumerName
     * @throws LocalizedException
     */
    protected function truncateConsumer($consumerName)
    {
        $topic = $this->getTopicFromConsumer($consumerName);
        $publisher = $this->publisherFactory->create($topic);
        $publisher->publish($topic, [Consumer::SHUTDOWN_PROTOCOL => true]);
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

    /**
     * Get topic from consumer name
     *
     * @param $consumerName
     * @return mixed
     * @throws LocalizedException
     */
    public function getTopicFromConsumer($consumerName)
    {
        $queueName = $this->getQueueByConsumer($consumerName);
        return $this->getTopicFromQueue($queueName);
    }

    /**
     * Get topic from queue name
     *
     * @param $queueName
     * @return mixed
     * @throws LocalizedException
     */
    public function getTopicFromQueue($queueName)
    {
        $topic = null;
        foreach($this->queueConfig->get(QueueConverter::BINDS) as $bind) {
            if ($bind[QueueConverter::BIND_QUEUE] == $queueName) {
                return $bind[QueueConverter::BIND_TOPIC];
            }
        }

        throw new LocalizedException(new Phrase('Queue %name has no topic binds', ['name' => $queueName]));
    }
}
