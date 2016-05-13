<?php
/**
 * @package     BlueAcorn\AmqpBase
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\AmqpBase\Model;

use BlueAcorn\AmqpBase\Api\Data\AlertInterface;
use Magento\Framework\DataObject;

class Alert extends DataObject implements AlertInterface
{
    /**
     * {@inheritdoc}
     */
    public function getMessage()
    {
        return $this->getData(self::MESSAGE);
    }

    /**
     * {@inheritdoc}
     */
    public function getStackTrace()
    {
        return $this->getData(self::STACK_TRACE);
    }

    /**
     * {@inheritdoc}
     */
    public function getConsumer()
    {
        return $this->getData(self::CONSUMER);
    }

    /**
     * {@inheritdoc}
     */
    public function getEmailSubject()
    {
        return $this->getData(self::EMAIL_SUBJECT);
    }

    /**
     * {@inheritdoc}
     */
    public function getEmailRecipients()
    {
        return $this->getData(self::EMAIL_RECIPIENTS);
    }

    /**
     * {@inheritdoc}
     */
    public function getTimestamp()
    {
        return $this->getData(self::TIMESTAMP);
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplateVars()
    {
        return $this->getData();
    }

    /**
     * {@inheritdoc}
     */
    public function setMessage($message)
    {
        return $this->setData(self::MESSAGE, $message);
    }

    /**
     * {@inheritdoc}
     */
    public function setStackTrace($stackTrace)
    {
        return $this->setData(self::STACK_TRACE, $stackTrace);
    }

    /**
     * {@inheritdoc}
     */
    public function setConsumer($consumer)
    {
        return $this->setData(self::CONSUMER, $consumer);
    }

    /**
     * {@inheritdoc}
     */
    public function setEmailSubject($emailSubject)
    {
        return $this->setData(self::EMAIL_SUBJECT, $emailSubject);
    }

    /**
     * {@inheritdoc}
     */
    public function setEmailRecipients($emailRecipients)
    {
        return $this->setData(self::EMAIL_RECIPIENTS, $emailRecipients);
    }

    /**
     * {@inheritdoc}
     */
    public function setTimestamp($timestamp)
    {
        return $this->setData(self::TIMESTAMP, $timestamp);
    }
}
