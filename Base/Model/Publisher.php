<?php
/**
 * @package     BlueAcorn\AmqpBase
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\AmqpBase\Model;

use Magento\Framework\MessageQueue\EnvelopeFactory;
use Magento\Framework\MessageQueue\ExchangeRepository;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\MessageQueue\Config\Data as MessageQueueConfig;

/**
 * A MessageQueue Publisher to handle publishing a message.
 * Override amqp publisher to allow setting lower level properties on messages
 */
class Publisher implements PublisherInterface
{
    /**
     * @var ExchangeRepository
     */
    protected $exchangeRepository;

    /**
     * @var EnvelopeFactory
     */
    protected $envelopeFactory;

    /**
     * @var MessageQueueConfig
     */
    protected $messageQueueConfig;

    /**
     * @var array
     */
    protected $defaultMessageProperties;

    /**
     * @var MessageEncoder
     */
    protected $messageEncoder;

    /**
     * Initialize dependencies.
     *
     * @param ExchangeRepository $exchangeRepository
     * @param EnvelopeFactory $envelopeFactory
     * @param MessageQueueConfig $messageQueueConfig
     * @param MessageEncoder $messageEncoder
     * @param array $defaultMessageProperties
     */
    public function __construct(
        ExchangeRepository $exchangeRepository,
        EnvelopeFactory $envelopeFactory,
        MessageQueueConfig $messageQueueConfig,
        MessageEncoder $messageEncoder,
        array $defaultMessageProperties
    ) {
        $this->exchangeRepository = $exchangeRepository;
        $this->envelopeFactory = $envelopeFactory;
        $this->messageQueueConfig = $messageQueueConfig;
        $this->defaultMessageProperties = $defaultMessageProperties;
        $this->messageEncoder = $messageEncoder;
    }

    /**
     * Publishes a message to a specific queue or exchange.
     *
     * @param string $topicName
     * @param array|object $data
     * @param array $properties
     * @return void
     */
    public function publish($topicName, $data, array $properties = [])
    {
        $properties = array_merge($this->defaultMessageProperties, $properties);
        $body = is_string($data) ? $data : $this->messageEncoder->encode($topicName, $data);
        $envelope = $this->envelopeFactory->create(['body' => $body, 'properties' => $properties]);
        $connectionName = $this->messageQueueConfig->getConnectionByTopic($topicName);
        $exchange = $this->exchangeRepository->getByConnectionName($connectionName);
        $exchange->enqueue($topicName, $envelope);
    }
}
