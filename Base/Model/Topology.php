<?php
/**
 * @package     BlueAcorn\AmqpBase
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\AmqpBase\Model;

use BlueAcorn\AmqpBase\Helper\LogManager;
use BlueAcorn\AmqpBase\Helper\MessageQueue\Config;
use Magento\Amqp\Model\Config as AmqpConfig;
use Magento\Framework\MessageQueue\Config\Converter as QueueConfigConverter;

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
     * @var LogManager
     */
    protected $logManager;

    /**
     * Initialize dependencies
     *
     * @param AmqpConfig $amqpConfig
     * @param Config $queueConfig
     * @param LogManager $logManager
     */
    public function __construct(
        AmqpConfig $amqpConfig,
        Config $queueConfig,
        LogManager $logManager
    ) {
        $this->amqpConfig = $amqpConfig;
        $this->queueConfig = $queueConfig;
        $this->logManager = $logManager;
    }

    /**
     * Install Amqp Exchanges, Queues and bind them
     *
     * @return void
     */
    public function install()
    {
        $queueConfig = $this->queueConfig->getQueueConfigData();
        if (isset($queueConfig[QueueConfigConverter::BINDS])) {
            $availableQueues = $this->queueConfig->getQueuesList(self::AMQP_CONNECTION);
            $availableExchanges = $this->queueConfig->getExchangesList(self::AMQP_CONNECTION);

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
                        $this->logManager->getLogger()->error(
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

}
