<?php
/**
 * @package     BlueAcorn\AmqpBase
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\AmqpBase\Model;

use Magento\Framework\MessageQueue\Config\Data as QueueConfig;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\MessageQueue\ConnectionLostException;
use Magento\Framework\MessageQueue\ConsumerConfigurationInterface;
use Magento\Framework\MessageQueue\EnvelopeInterface;
use Magento\Framework\MessageQueue\QueueInterface;
use Magento\Framework\MessageQueue\ConsumerInterface;
use Magento\Framework\MessageQueue\QueueRepository;
use Magento\Framework\Phrase;
use Magento\Framework\MessageQueue\Config\Converter as QueueConfigConverter;
use Magento\Framework\App\ResourceConnection;
use BlueAcorn\AmqpBase\Helper\LogManager;
use BlueAcorn\AmqpBase\Helper\Consumer\Config as ConsumerConfig;

/**
 * A MessageQueue Consumer to handle receiving a message.
 * Rewritten to accept messages with a 'shutdown' protocol
 */
class Consumer implements ConsumerInterface
{
    /**
     * If protocol found within message, exit this consumer
     */
    const SHUTDOWN_PROTOCOL = 'CONSUMER_SHUTDOWN';

    /**
     * @var QueueConfig
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
    protected $shutdownFlag;

    /**
     * @var LogManager
     */
    protected $logManager;

    /**
     * @var AlertManager
     */
    protected $alertManager;

    /**
     * @var ConsumerConfig
     */
    protected $consumerConfig;

    /**
     * Initialize dependencies.
     *
     * @param QueueConfig $messageQueueConfig
     * @param MessageEncoder $messageEncoder
     * @param QueueRepository $queueRepository
     * @param LogManager $logManager
     * @param AlertManager $alertManager
     * @param ConsumerConfig $consumerConfig
     * @param ResourceConnection $resource
     */
    public function __construct(
        QueueConfig $messageQueueConfig,
        MessageEncoder $messageEncoder,
        QueueRepository $queueRepository,
        LogManager $logManager,
        AlertManager $alertManager,
        ConsumerConfig $consumerConfig,
        ResourceConnection $resource
    ) {
        $this->messageQueueConfig = $messageQueueConfig;
        $this->messageEncoder = $messageEncoder;
        $this->queueRepository = $queueRepository;
        $this->resource = $resource;
        $this->logManager = $logManager;
        $this->alertManager = $alertManager;
        $this->consumerConfig = $consumerConfig;
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
            // If message decoded to SHUTDOWN, set property flag and skip normal callback procedure
            if ($decodedMessage === self::SHUTDOWN_PROTOCOL) {
                $this->logManager->getLogger()->debug('Shutdown protocol found');
                $this->shutdownFlag = true;
                return;
            }
            $messageSchemaType = $this->messageQueueConfig->getMessageSchemaType($topicName);
            if ($messageSchemaType == QueueConfigConverter::TOPIC_SCHEMA_TYPE_METHOD) {
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
        $this->logManager->getLogger()->debug('Starting daemon mode...');
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
                if ($this->shutdownFlag) {
                    $this->shutdown();
                }
            } catch (ConsumptionUnfinishedException $e) {
                $this->alert($e);
                /**
                 * Only reject messages for this exception type (reject will re-enqueue messages)
                 * Notice changes are still committed when this exception is thrown
                 */
                $this->resource->getConnection()->commit();
                $queue->reject($message);
            } catch (ConnectionLostException $e) {
                $this->alert($e);
                /**
                 * If a connection is lost, there is nothing we can do other than rollback transaction
                 */
                $this->resource->getConnection()->rollBack();
            } catch (\Exception $e) {
                $this->alert($e);
                /**
                 * Always acknowledge to avoid infinite message loop
                 */
                $queue->acknowledge($message);
                $this->resource->getConnection()->rollBack();
            }
        };
    }

    /**
     * Aggregate consumer coniguration to build and publish an alert
     *
     * @param \Exception $e
     */
    public function alert(\Exception $e)
    {
        $this->logManager->getLogger()->debug($e);
        $consumerName = $this->configuration->getConsumerName();
        $config = $this->consumerConfig->getConsumerConfig($consumerName);
        if ($config[ConsumerConfig::FIELD_EMAIL_RECIPIENTS]) {
            $alert = $this->alertManager->getBuilder()
                ->setEmailRecipients($config[ConsumerConfig::FIELD_EMAIL_RECIPIENTS])
                ->setEmailSubject($config[ConsumerConfig::FIELD_EMAIL_SUBJECT])
                ->setConsumer($consumerName)
                ->setMessage($e->getMessage())
                ->setStackTrace($e->getTraceAsString())
                ->setTimestamp(time())
                ->create();
            $this->alertManager->publish($alert);
        } else {
            $this->logManager->getLogger()->debug(new Phrase(
                'Exception was generated, but no email recipients are configured for "%consumer"',
                ['consumer' => $consumerName]
            ));
        }
    }

    /**
     * Shuts down this consumer process
     * TODO: See if we should use a channel::basic_cancel before exiting!
     */
    protected function shutdown()
    {
        $this->logManager->getLogger()->debug('Shutting down now');
        exit(0);
    }
}
