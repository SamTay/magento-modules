<?php
/**
 * @package     BlueAcorn\AmqpBase
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\AmqpBase\Model;

use Magento\Framework\DataObject\Factory as DataObjectFactory;
use Magento\Framework\Json\DecoderInterface as JsonDecoderInterface;
use Magento\Framework\Json\EncoderInterface as JsonEncoderInterface;
use Magento\Framework\MessageQueue\Config\Data as QueueConfig;
use Magento\Framework\MessageQueue\Config\Converter as QueueConfigConverter;
use Magento\Framework\Webapi\ServiceInputProcessor;
use Magento\Framework\Webapi\ServiceOutputProcessor;
use Magento\Framework\Webapi\ServicePayloadConverterInterface;
use Magento\Framework\Event\Manager as EventManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;

/**
 * Class which provides encoding and decoding capabilities for MessageQueue messages.
 * Rewritten to handle shutdown message decoding
 */
class MessageEncoder
{
    const DIRECTION_ENCODE = 'encode';
    const DIRECTION_DECODE = 'decode';

    /**
     * @var QueueConfig
     */
    private $queueConfig;

    /**
     * @var ServiceOutputProcessor
     */
    private $dataObjectEncoder;

    /**
     * @var ServiceInputProcessor
     */
    private $dataObjectDecoder;

    /**
     * @var JsonEncoderInterface
     */
    private $jsonEncoder;

    /**
     * @var JsonDecoderInterface
     */
    private $jsonDecoder;

    /**
     * @var EventManager
     */
    private $eventManager;

    /**
     * @var DataObjectFactory
     */
    private $dataObjectFactory;

    /**
     * Initialize dependencies.
     *
     * @param QueueConfig $queueConfig
     * @param JsonEncoderInterface $jsonEncoder
     * @param JsonDecoderInterface $jsonDecoder
     * @param ServiceOutputProcessor $dataObjectEncoder
     * @param ServiceInputProcessor $dataObjectDecoder
     * @param EventManager $eventManager
     * @param DataObjectFactory $dataObjectFactory
     */
    public function __construct(
        QueueConfig $queueConfig,
        JsonEncoderInterface $jsonEncoder,
        JsonDecoderInterface $jsonDecoder,
        ServiceOutputProcessor $dataObjectEncoder,
        ServiceInputProcessor $dataObjectDecoder,
        EventManager $eventManager,
        DataObjectFactory $dataObjectFactory
    ) {
        $this->queueConfig = $queueConfig;
        $this->dataObjectEncoder = $dataObjectEncoder;
        $this->dataObjectDecoder = $dataObjectDecoder;
        $this->jsonEncoder = $jsonEncoder;
        $this->jsonDecoder = $jsonDecoder;
        $this->eventManager = $eventManager;
        $this->dataObjectFactory = $dataObjectFactory;
    }

    /**
     * Encode message content based on current topic.
     *
     * @param string $topic
     * @param mixed $message
     * @return string
     * @throws LocalizedException
     */
    public function encode($topic, $message)
    {
        $convertedMessage = $this->convertMessage($topic, $message, self::DIRECTION_ENCODE);
        return $this->jsonEncoder->encode($convertedMessage);
    }

    /**
     * Decode message content based on current topic.
     *
     * @param string $topic
     * @param string $message
     * @return mixed
     * @throws LocalizedException
     */
    public function decode($topic, $message)
    {
        try {
            $decodedMessage = $this->jsonDecoder->decode($message);
        } catch (\Exception $e) {
            throw new LocalizedException(new Phrase("Error occurred during message decoding."));
        }
        return $this->convertMessage($topic, $decodedMessage, self::DIRECTION_DECODE);
    }

    /**
     * Identify message data schema by topic.
     *
     * @param string $topic
     * @return array
     * @throws LocalizedException
     */
    protected function getTopicSchema($topic)
    {
        $queueConfig = $this->queueConfig->get();
        if (isset($queueConfig[QueueConfigConverter::TOPICS][$topic])) {
            return $queueConfig[QueueConfigConverter::TOPICS][$topic][QueueConfigConverter::TOPIC_SCHEMA];
        }
        throw new LocalizedException(new Phrase('Specified topic "%topic" is not declared.', ['topic' => $topic]));
    }

    /**
     * Convert message according to the format associated with its topic using provided converter.
     * Optionally returns a flat shutdown protocol string if found in $message
     * {@inheritdoc}
     */
    protected function convertMessage($topic, $message, $direction)
    {
        if (isset($message[Consumer::SHUTDOWN_PROTOCOL])
            && $message[Consumer::SHUTDOWN_PROTOCOL]
        ) {
            return Consumer::SHUTDOWN_PROTOCOL;
        }

        $topicSchema = $this->getTopicSchema($topic);
        if ($topicSchema[QueueConfigConverter::TOPIC_SCHEMA_TYPE] == QueueConfigConverter::TOPIC_SCHEMA_TYPE_OBJECT) {
            /** Convert message according to the data interface associated with the message topic */
            $messageDataType = $topicSchema[QueueConfigConverter::TOPIC_SCHEMA_VALUE];
            try {
                $convertedMessage = $this->convertValue($message, $messageDataType, $direction);
            } catch (LocalizedException $e) {
                throw new LocalizedException(
                    new Phrase(
                        'Message with topic "%topic" must be an instance of "%class".',
                        ['topic' => $topic, 'class' => $messageDataType]
                    )
                );
            }
        } else {
            /** Convert message according to the method signature associated with the message topic */
            $message = (array)$message;
            $isIndexedArray = array_keys($message) === range(0, count($message) - 1);
            $convertedMessage = [];
            /** Message schema type is defined by method signature */
            foreach ($topicSchema[QueueConfigConverter::TOPIC_SCHEMA_VALUE] as $methodParameterMeta) {
                $paramName = $methodParameterMeta[QueueConfigConverter::SCHEMA_METHOD_PARAM_NAME];
                $paramType = $methodParameterMeta[QueueConfigConverter::SCHEMA_METHOD_PARAM_TYPE];
                if ($isIndexedArray) {
                    /** Encode parameters according to their positions in method signature */
                    $paramPosition = $methodParameterMeta[QueueConfigConverter::SCHEMA_METHOD_PARAM_POSITION];
                    if (isset($message[$paramPosition])) {
                        $convertedMessage[$paramName] = $this->convertValue($message[$paramPosition], $paramType, $direction);
                    }
                } else {
                    /** Encode parameters according to their names in method signature */
                    if (isset($message[$paramName])) {
                        $convertedMessage[$paramName] = $this->convertValue($message[$paramName], $paramType, $direction);
                    }
                }

                /** Ensure that all required params were passed */
                if ($methodParameterMeta[QueueConfigConverter::SCHEMA_METHOD_PARAM_IS_REQUIRED]
                    && !isset($convertedMessage[$paramName])
                ) {
                    throw new LocalizedException(
                        new Phrase(
                            'Data item corresponding to "%param" of "%method" must be specified '
                            . 'in the message with topic "%topic".',
                            [
                                'topic' => $topic,
                                'param' => $paramName,
                                'method' => $topicSchema[QueueConfigConverter::TOPIC_SCHEMA_METHOD_NAME]
                            ]
                        )
                    );
                }
            }
        }
        return $convertedMessage;
    }

    /**
     * Get value converter based on conversion direction.
     *
     * @param string $direction
     * @return ServicePayloadConverterInterface
     */
    protected function getConverter($direction)
    {
        return ($direction == self::DIRECTION_ENCODE) ? $this->dataObjectEncoder : $this->dataObjectDecoder;
    }

    /**
     * Wrap native value conversion with events so that entity mapper can be injected
     *
     * @param $value
     * @param $type
     * @param $direction
     * @return mixed
     */
    protected function convertValue($value, $type, $direction)
    {
        $transport = $this->dataObjectFactory->create(['message' => $value]);
        $this->eventManager->dispatch('amqp_message_convert_before', [
            'schema' => $type,
            'transport' => $transport,
            'direction' => $direction
        ]);

        $value = $transport->getMessage();
        $value = $this->getConverter($direction)->convertValue($value, $type);
        $transport->setMessage($value);
        $this->eventManager->dispatch('amqp_message_convert_after', [
            'schema' => $type,
            'transport' => $transport,
            'direction' => $direction
        ]);

        return $transport->getMessage();
    }
}
