<?php
/**
 * @package     BlueAcorn\AmqpBase
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\AmqpBase\Model\Consumer;

use Magento\Framework\MessageQueue\Config\Data as MessageQueueConfig;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\MessageQueue\ConnectionLostException;
use Magento\Framework\MessageQueue\ConsumerConfigurationInterface;
use Magento\Framework\MessageQueue\EnvelopeInterface;
use Magento\Framework\MessageQueue\MessageEncoder;
use Magento\Framework\MessageQueue\QueueInterface;
use Magento\Framework\MessageQueue\QueueRepository;
use Magento\Framework\Phrase;
use Magento\Framework\MessageQueue\Config\Converter as MessageQueueConfigConverter;
use Magento\Framework\App\ResourceConnection;

/**
 * A MessageQueue Consumer to handle receiving a message.
 * Rewritten to accept messages with a 'shutdown' protocol
 */
class Consumer implements ConsumerInterface
{
    /**
     * @var MessageQueueConfig
     */
    protected $messageQueueConfig;

    /**
     * @var MessageEncoder
     */
    protected $messageEncoder;

    /**
     * @var ConsumerConfigurationInterface
     */
    protected $configuration;

    /**
     * @var QueueRepository
     */
    protected $queueRepository;

    /**
     * @var ResourceConnection
     */
    protected $resource;

    /**
     * @var bool
     */
    protected $shutdown;

    /**
     * Initialize dependencies.
     *
     * @param MessageQueueConfig $messageQueueConfig
     * @param MessageEncoder $messageEncoder
     * @param QueueRepository $queueRepository
     * @param ResourceConnection $resource
     */
    public function __construct(
        MessageQueueConfig $messageQueueConfig,
        MessageEncoder $messageEncoder,
        QueueRepository $queueRepository,
        ResourceConnection $resource
    ) {
        $this->messageQueueConfig = $messageQueueConfig;
        $this->messageEncoder = $messageEncoder;
        $this->queueRepository = $queueRepository;
        $this->resource = $resource;
    }

    /**
     * {@inheritdoc}
     */
    public function configure(ConsumerConfigurationInterface $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * {@inheritdoc}
     */
    public function process($maxNumberOfMessages = null)
    {
        $queue = $this->getQueue();

        if (!isset($maxNumberOfMessages)) {
            $this->runDaemonMode($queue);
        } else {
            $this->run($queue, $maxNumberOfMessages);
        }
    }

    /**
     * Decode message and invoke callback method
     *
     * @param EnvelopeInterface $message
     * @return void
     * @throws LocalizedException
     */
    protected function dispatchMessage(EnvelopeInterface $message)
    {
        $properties = $message->getProperties();
        $topicName = $properties['topic_name'];
        $callback = $this->configuration->getCallback();

        $decodedMessage = $this->messageEncoder->decode($topicName, $message->getBody());

        if (isset($decodedMessage)) {
            $messageSchemaType = $this->messageQueueConfig->getMessageSchemaType($topicName);
            if ($messageSchemaType == MessageQueueConfigConverter::TOPIC_SCHEMA_TYPE_METHOD) {
                call_user_func_array($callback, $decodedMessage);
            } else {
                call_user_func($callback, $decodedMessage);
            }
        }
    }

    /**
     * Run short running process
     *
     * @param QueueInterface $queue
     * @param int $maxNumberOfMessages
     * @return void
     */
    protected function run(QueueInterface $queue, $maxNumberOfMessages)
    {
        $count = $maxNumberOfMessages
            ? $maxNumberOfMessages
            : $this->configuration->getMaxMessages() ?: 1;

        $transactionCallback = $this->getTransactionCallback($queue);
        for ($i = $count; $i > 0; $i--) {
            $message = $queue->dequeue();
            if ($message === null) {
                break;
            }
            $transactionCallback($message);
        }
    }

    /**
     * Run process in the daemon mode
     *
     * @param QueueInterface $queue
     * @return void
     */
    protected function runDaemonMode(QueueInterface $queue)
    {
        $callback = $this->getTransactionCallback($queue);

        $queue->subscribe($callback);
    }

    /**
     * @return QueueInterface
     * @throws LocalizedException
     */
    protected function getQueue()
    {
        $queueName = $this->configuration->getQueueName();
        $consumerName = $this->configuration->getConsumerName();
        $connectionName = $this->messageQueueConfig->getConnectionByConsumer($consumerName);
        $queue = $this->queueRepository->get($connectionName, $queueName);

        return $queue;
    }

    /**
     * @param QueueInterface $queue
     * @return \Closure
     */
    protected function getTransactionCallback(QueueInterface $queue)
    {
        return function (EnvelopeInterface $message) use ($queue) {
            try {
                $this->resource->getConnection()->beginTransaction();
                $this->dispatchMessage($message);
                $queue->acknowledge($message);
                $this->resource->getConnection()->commit();
            } catch (ConnectionLostException $e) {
                $this->resource->getConnection()->rollBack();
            } catch (\Exception $e) {
                $this->resource->getConnection()->rollBack();
                $queue->reject($message);
            }
        };
    }
}
