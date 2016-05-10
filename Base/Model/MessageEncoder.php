<?php
/**
 * @package     BlueAcorn\AmqpBase
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\AmqpBase\Model;

/**
 * Class which provides encoding and decoding capabilities for MessageQueue messages.
 * Rewritten to handle shutdown message decoding
 */
class MessageEncoder extends \Magento\Framework\MessageQueue\MessageEncoder
{
    /**
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

        return parent::convertMessage($topic, $message, $direction);
    }
}
