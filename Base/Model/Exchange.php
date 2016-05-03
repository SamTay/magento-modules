<?php
/**
 * @package     BlueAcorn\AmqpBase
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\AmqpBase\Model;

use Magento\Framework\MessageQueue\EnvelopeInterface;
use Magento\Framework\MessageQueue\ExchangeInterface;
use Magento\Framework\MessageQueue\Config\Data as QueueConfig;
use Magento\Amqp\Model\Config as AmqpConfig;
use PhpAmqpLib\Message\AMQPMessage;

class Exchange implements ExchangeInterface
{
    /**
     * @var AmqpConfig
     */
    protected $amqpConfig;

    /**
     * @var QueueConfig
     */
    protected $queueConfig;

    /**
     * Exchange constructor.
     * @param AmqpConfig $amqpConfig
     * @param QueueConfig $queueConfig
     */
    public function __construct(AmqpConfig $amqpConfig, QueueConfig $queueConfig)
    {
        $this->amqpConfig = $amqpConfig;
        $this->queueConfig = $queueConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function enqueue($topic, EnvelopeInterface $envelope)
    {
        $channel = $this->amqpConfig->getChannel();
        $exchange = $this->queueConfig->getExchangeByTopic($topic);

        $msg = new AMQPMessage(
            $envelope->getBody(),
            $envelope->getProperties()
        );
        $channel->basic_publish($msg, $exchange, $topic);
    }
}
