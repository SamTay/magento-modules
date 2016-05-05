<?php
/**
 * @package     BlueAcorn\AmqpBase
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\AmqpBase\Model;

use Magento\Amqp\Model\Config as AmqpConfig;
use Magento\Framework\Json\Decoder;
use Magento\Framework\Json\Encoder;
use Magento\Framework\MessageQueue\EnvelopeFactory;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Class Rpc
 * Publish messages with optional callbacks, utilizing RPC
 */
class Rpc
{
    /**
     * @var Exchange
     */
    protected $exchange;

    /**
     * @var Publisher
     */
    protected $publisher;

    /**
     * @var AmqpConfig
     */
    protected $amqpConfig;

    /**
     * @var EnvelopeFactory
     */
    protected $envelopeFactory;

    /**
     * @var array bool[]
     */
    protected $_responseReceived = [];

    /**
     * @var Encoder
     */
    protected $jsonEncoder;

    /**
     * @var Decoder
     */
    protected $jsonDecoder;

    /**
     * Rpc constructor.
     * @param Exchange $exchange
     * @param Publisher $publisher
     * @param EnvelopeFactory $envelopeFactory
     * @param AmqpConfig $amqpConfig
     * @param Encoder $jsonEncoder
     * @param Decoder $jsonDecoder
     */
    public function __construct(
        Exchange $exchange,
        Publisher $publisher,
        EnvelopeFactory $envelopeFactory,
        AmqpConfig $amqpConfig,
        Encoder $jsonEncoder,
        Decoder $jsonDecoder
    ) {

        $this->exchange = $exchange;
        $this->publisher = $publisher;
        $this->amqpConfig = $amqpConfig;
        $this->envelopeFactory = $envelopeFactory;
        $this->jsonEncoder = $jsonEncoder;
        $this->jsonDecoder = $jsonDecoder;
    }

    /**
     * Publish a message with callback on reception (RPC)
     * Warning: This is blocking!
     *
     * @param string $topicName
     * @param array|object $data
     * @param callable $callback
     */
    public function publish($topicName, $data, callable $callback)
    {
        // Set up callback and correlation ID link
        $correlationId = uniqid();
        $this->_responseReceived[$correlationId] = false;
        $convertedCallback = $this->_getConvertedCallback($callback, $correlationId);

        // Set up callback queue and consumer
        $channel = $this->amqpConfig->getChannel();
        // TODO: constants for all of these booleans !!
        $callbackQueue = $channel->queue_declare('', false, false, true, false)[0];
        $channel->basic_consume($callbackQueue, '', false, false, false, false, $convertedCallback);

        // Publish message
        // May want to revisit this later on to see if we should inject the mqf message encoder (which encodes
        // entire class/interface structures based on getter/setter schema)
        $encodedMessage = $this->jsonEncoder->encode($data);
        $this->publisher->publish($topicName, $encodedMessage,
            [
                'correlation_id' => $correlationId,
                'reply_to' => $callbackQueue
            ]
        );

        // Wait until message has been received
        while (!$this->_responseReceived[$correlationId]) {
            $channel->wait();
        }
    }

    /**
     * Convert callback to amqp spec to separate concern from other modules
     *
     * @param callable $callback
     * @param $correlationId
     * @return \Closure
     */
    protected function _getConvertedCallback(callable $callback, $correlationId)
    {
        return function(AMQPMessage $message) use ($callback, $correlationId) {
            if ($message->get('correlation_id') != $correlationId) {
                return;
            }
            $this->_responseReceived[$correlationId] = true;
            $decodedMessage = $this->jsonDecoder->decode($message->body);
            call_user_func($callback, $decodedMessage);
        };
    }
}
