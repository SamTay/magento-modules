<?php
/**
 * @package     BlueAcorn\AmqpBase
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\AmqpBase\Model\Consumer;

use BlueAcorn\AmqpBase\Model\Topology;
use BlueAcorn\AmqpBase\Helper\Consumer\Config as ConsumerConfig;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\MessageQueue\Config\Data as QueueConfig;
use Magento\Framework\MessageQueue\Config\Converter as QueueConverter;

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
     * Daemonizer constructor.
     * @param Topology $topology
     * @param QueueConfig $queueConfig
     * @param ConsumerConfig $consumerConfig
     */
    public function __construct(
        Topology $topology,
        QueueConfig $queueConfig,
        ConsumerConfig $consumerConfig
    ) {
        $this->topology = $topology;
        $this->queueConfig = $queueConfig;
        $this->consumerConfig = $consumerConfig;
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
            throw new LocalizedException(sprintf('No queue specified for consumer %s', $consumerName));
        }

        return $queueName;
    }
}
