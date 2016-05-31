<?php
/**
 * @package     BlueAcorn\AmqpBase
 * @version     0.2.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\AmqpBase\Helper\MessageQueue;

use Magento\Framework\MessageQueue\Config\Data as QueueConfig;
use Magento\Framework\MessageQueue\Config\Converter as QueueConfigConverter;
use BlueAcorn\AmqpBase\Helper\LogManager;
use Magento\Framework\Phrase;

class Config
{
    /**
     * Amqp connection
     */
    const AMQP_CONNECTION = 'amqp';

    /**
     * @var QueueConfig
     */
    protected $queueConfig;

    /**
     * @var array
     */
    protected $queueConfigData;

    /**
     * @var LogManager
     */
    protected $logManager;

    /**
     * Initialize dependencies
     *
     * @param QueueConfig $queueConfig
     * @param LogManager $logManager
     */
    public function __construct(
        QueueConfig $queueConfig,
        LogManager $logManager
    ) {
        $this->queueConfig = $queueConfig;
        $this->logManager = $logManager;
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
        foreach($this->queueConfig->get(QueueConfigConverter::BINDS) as $bind) {
            if ($bind[QueueConfigConverter::BIND_QUEUE] == $queueName) {
                return $bind[QueueConfigConverter::BIND_TOPIC];
            }
        }

        throw new LocalizedException(new Phrase('Queue "%name" has no topic binds', ['name' => $queueName]));
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
            QueueConfigConverter::CONSUMERS,
            $consumerName,
            QueueConfigConverter::CONSUMER_QUEUE
        ]);
        $queueName = $this->queueConfig->get($path);
        if (!$queueName) {
            throw new LocalizedException(
                new Phrase('No queue specified for consumer "%name"', ['name' => $consumerName])
            );
        }

        return $queueName;
    }

    /**
     * Return list of queue names that are available for connection
     *
     * @param string $connection
     * @return array List of queue names
     */
    public function getQueuesList($connection = self::AMQP_CONNECTION)
    {
        $queues = [];
        $queueConfig = $this->getQueueConfigData();
        if (isset($queueConfig[QueueConfigConverter::CONSUMERS])) {
            foreach ($queueConfig[QueueConfigConverter::CONSUMERS] as $consumer) {
                if ($consumer[QueueConfigConverter::CONSUMER_CONNECTION] === $connection) {
                    $queues[] = $consumer[QueueConfigConverter::CONSUMER_QUEUE];
                }
            }
            $queues = array_unique($queues);
        }
        return $queues;
    }

    /**
     * Return list of exchange names that are available for connection
     *
     * @param string $connection
     * @return array List of exchange names
     */
    public function getExchangesList($connection = self::AMQP_CONNECTION)
    {
        $exchanges = [];
        $queueConfig = $this->getQueueConfigData();
        if (isset($queueConfig[QueueConfigConverter::PUBLISHERS])) {
            foreach ($queueConfig[QueueConfigConverter::PUBLISHERS] as $publisher) {
                if ($publisher[QueueConfigConverter::PUBLISHER_CONNECTION] === $connection) {
                    $exchanges[] = $publisher[QueueConfigConverter::PUBLISHER_EXCHANGE];
                }
            }
            $exchanges = array_unique($exchanges);
        }
        return $exchanges;
    }

    /**
     * Return list of consumer names that are available for connection
     *
     * @param string $connection
     * @return array
     */
    public function getConsumersList($connection = self::AMQP_CONNECTION)
    {
        $consumers = [];
        $queueConfig = $this->getQueueConfigData();
        if (isset($queueConfig[QueueConfigConverter::CONSUMERS])) {
            foreach ($queueConfig[QueueConfigConverter::CONSUMERS] as $consumer) {
                if ($consumer[QueueConfigConverter::CONSUMER_CONNECTION] === $connection) {
                    $consumers[] = $consumer[QueueConfigConverter::CONSUMER_NAME];
                }
            }
            $consumers = array_unique($consumers);
        }
        return $consumers;
    }

    /**
     * Returns the queue configuration.
     *
     * @return array
     */
    public function getQueueConfigData()
    {
        if ($this->queueConfigData == null) {
            $this->queueConfigData = $this->queueConfig->get();
        }
        return $this->queueConfigData;
    }

    /**
     * Check if queueName exists in configuration
     *
     * @param $queueName
     * @param string $connection
     * @return bool
     */
    public function isValidQueueName($queueName, $connection = self::AMQP_CONNECTION)
    {
        $availableQueues = $this->getQueuesList($connection);
        return in_array($queueName, $availableQueues);
    }

    /**
     * Get max_messages configured for consumer
     *
     * @param string $consumerName
     * @return string|int|null
     */
    public function getMaxMessages($consumerName)
    {
        $path = implode('/', [
            QueueConfigConverter::CONSUMERS,
            $consumerName,
            QueueConfigConverter::CONSUMER_MAX_MESSAGES
        ]);
        return $this->queueConfig->get($path);
    }
}
