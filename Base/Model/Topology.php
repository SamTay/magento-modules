<?php
/**
 * @package     BlueAcorn\AmqpBase
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\AmqpBase\Model;

use BlueAcorn\AmqpBase\Helper\Logger;
use Magento\Amqp\Model\Config as AmqpConfig;
use Magento\Framework\MessageQueue\Config\Data as QueueConfig;
use Magento\Framework\MessageQueue\Config\Converter as QueueConfigConverter;
use PhpAmqpLib\Exception\AMQPInvalidArgumentException;

/**
 * Class Topology creates topology for Amqp messaging
 * Rewritten to standardize declarations with constants
 * and log to custom file
 */
class Topology
{
    /**
     * Type of exchange
     */
    const TOPIC_EXCHANGE = 'topic';

    /**
     * Amqp connection
     */
    const AMQP_CONNECTION = 'amqp';

    /**
     * Declaration options for queues & exchanges
     */
    const IS_DURABLE = true;
    const IS_PASSIVE = false;
    const IS_AUTO_DELETE = false;

    /**
     * Declaration options for queues
     */
    const IS_EXCLUSIVE = false;

    /**
     * @var AmqpConfig
     */
    protected $amqpConfig;

    /**
     * @var QueueConfig
     */
    protected $queueConfig;

    /**
     * @var array
     */
    protected $queueConfigData;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * Initialize dependencies
     *
     * @param AmqpConfig $amqpConfig
     * @param QueueConfig $queueConfig
     * @param Logger $logger
     */
    public function __construct(
        AmqpConfig $amqpConfig,
        QueueConfig $queueConfig,
        Logger $logger
    ) {
        $this->amqpConfig = $amqpConfig;
        $this->queueConfig = $queueConfig;
        $this->logger = $logger;
    }

    /**
     * Install Amqp Exchanges, Queues and bind them
     *
     * @return void
     */
    public function install()
    {
        $queueConfig = $this->getQueueConfigData();
        if (isset($queueConfig[QueueConfigConverter::BINDS])) {
            $availableQueues = $this->getQueuesList(self::AMQP_CONNECTION);
            $availableExchanges = $this->getExchangesList(self::AMQP_CONNECTION);

            foreach ($queueConfig[QueueConfigConverter::BINDS] as $bind) {
                $queueName = $bind[QueueConfigConverter::BIND_QUEUE];
                $exchangeName = $bind[QueueConfigConverter::BIND_EXCHANGE];
                $topicName = $bind[QueueConfigConverter::BIND_TOPIC];
                if (in_array($queueName, $availableQueues) && in_array($exchangeName, $availableExchanges)) {
                    try {
                        $this->declareQueue($queueName);
                        $this->declareExchange($exchangeName);
                        $this->bindQueue($queueName, $exchangeName, $topicName);
                    } catch (\PhpAmqpLib\Exception\AMQPExceptionInterface $e) {
                        $this->logger->error(
                            sprintf(
                                'There is a problem with creating or binding queue "%s" and an exchange "%s". Error:',
                                $queueName,
                                $exchangeName,
                                $e->getTraceAsString()
                            )
                        );
                    }
                }
            }
        }
    }

    /**
     * Get number of consumers currently subscribed to queue
     * TODO: Investigate passive queue declaration to check for existence. May or may not close connection
     * on IO exception, but could be a cleaner solution than this
     *
     * @param $queueName
     * @return int
     */
    public function getConsumerCount($queueName)
    {
        if (!$this->isValidQueueName($queueName)) {
            throw new AMQPInvalidArgumentException(
                sprintf('The queue %s has not been properly declared in queue.xml.', $queueName)
            );
        }
        list(,,$consumerCount) = $this->declareQueue($queueName);
        return (int)$consumerCount;
    }

    /**
     * Declare Amqp Queue
     *
     * @param string $queueName
     * @return string[]
     */
    protected function declareQueue($queueName)
    {
        return $this->getChannel()->queue_declare($queueName, self::IS_PASSIVE, self::IS_DURABLE, self::IS_EXCLUSIVE, self::IS_AUTO_DELETE);
    }

    /**
     * Declare Amqp Exchange
     *
     * @param string $exchangeName
     * @return void
     */
    protected function declareExchange($exchangeName)
    {
        $this->getChannel()->exchange_declare($exchangeName, self::TOPIC_EXCHANGE, self::IS_PASSIVE, self::IS_DURABLE, self::IS_AUTO_DELETE);
    }

    /**
     * Bind queue and exchange
     *
     * @param string $queueName
     * @param string $exchangeName
     * @param string $topicName
     * @return void
     */
    protected function bindQueue($queueName, $exchangeName, $topicName)
    {
        $this->getChannel()->queue_bind($queueName, $exchangeName, $topicName);
    }

    /**
     * Return Amqp channel
     *
     * @return \PhpAmqpLib\Channel\AMQPChannel
     */
    protected function getChannel()
    {
        return $this->amqpConfig->getChannel();
    }

    /**
     * Return list of queue names, that are available for connection
     *
     * @param string $connection
     * @return array List of queue names
     */
    protected function getQueuesList($connection)
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
     * Return list of exchange names, that are available for connection
     *
     * @param string $connection
     * @return array List of exchange names
     */
    protected function getExchangesList($connection)
    {
        $exchanges = [];
        $queueConfig = $this->getQueueConfigData();
        if (isset($queueConfig[QueueConfigConverter::PUBLISHERS])) {
            foreach ($queueConfig[QueueConfigConverter::PUBLISHERS] as $consumer) {
                if ($consumer[QueueConfigConverter::PUBLISHER_CONNECTION] === $connection) {
                    $exchanges[] = $consumer[QueueConfigConverter::PUBLISHER_EXCHANGE];
                }
            }
            $exchanges = array_unique($exchanges);
        }
        return $exchanges;
    }

    /**
     * Returns the queue configuration.
     *
     * @return array
     */
    protected function getQueueConfigData()
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
     * @return bool
     */
    public function isValidQueueName($queueName)
    {
        $availableQueues = $this->getQueuesList(self::AMQP_CONNECTION);
        return in_array($queueName, $availableQueues);
    }
}
