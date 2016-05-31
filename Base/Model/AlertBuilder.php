<?php
/**
 * @package     BlueAcorn\AmqpBase
 * @version     0.2.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\AmqpBase\Model;

use Magento\Framework\Api\AbstractSimpleObjectBuilder;

class AlertBuilder extends AbstractSimpleObjectBuilder
{
    /**
     * @param string $message
     * @return \BlueAcorn\AmqpBase\Model\Alert
     */
    public function setMessage($message)
    {
        return $this->_set(Alert::MESSAGE, $message);
    }

    /**
     * @param string $stackTrace
     * @return \BlueAcorn\AmqpBase\Model\Alert
     */
    public function setStackTrace($stackTrace)
    {
        return $this->_set(Alert::STACK_TRACE, $stackTrace);
    }

    /**
     * @param string $consumer
     * @return \BlueAcorn\AmqpBase\Model\Alert
     */
    public function setConsumer($consumer)
    {
        return $this->_set(Alert::CONSUMER, $consumer);
    }

    /**
     * @param string $emailSubject
     * @return \BlueAcorn\AmqpBase\Model\Alert
     */
    public function setEmailSubject($emailSubject)
    {
        return $this->_set(Alert::EMAIL_SUBJECT, $emailSubject);
    }

    /**
     * @param string[] $emailRecipients
     * @return \BlueAcorn\AmqpBase\Model\Alert
     */
    public function setEmailRecipients($emailRecipients)
    {
        return $this->_set(Alert::EMAIL_RECIPIENTS, $emailRecipients);
    }

    /**
     * @param int $timestamp
     * @return \BlueAcorn\AmqpBase\Model\Alert
     */
    public function setTimestamp($timestamp)
    {
        return $this->_set(Alert::TIMESTAMP, $timestamp);
    }
}

