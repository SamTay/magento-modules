<?php
/**
 * @package     BlueAcorn\AmqpBase
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\AmqpBase\Model;

use Magento\Framework\MessageQueue\Config\Converter as QueueConfigConverter;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;

/**
 * Class which provides encoding and decoding capabilities for MessageQueue messages.
 * Rewritten to handle shutdown message decoding
 */
class MessageEncoder extends \Magento\Framework\MessageQueue\MessageEncoder
{
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
                $convertedMessage = $this->getConverter($direction)->convertValue($message, $messageDataType);
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
                        $convertedMessage[$paramName] = $this->getConverter($direction)
                            ->convertValue($message[$paramPosition], $paramType);
                    }
                } else {
                    /** Encode parameters according to their names in method signature */
                    if (isset($message[$paramName])) {
                        $convertedMessage[$paramName] = $this->getConverter($direction)
                            ->convertValue($message[$paramName], $paramType);
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
}
