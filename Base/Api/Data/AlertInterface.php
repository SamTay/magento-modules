<?php
/**
 * @package     BlueAcorn\AmqpBase
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\AmqpBase\Api\Data;

interface AlertInterface
{
    /**
     * Constants for keys of data array. Identical to the name of the getter in snake case
     */
    const MESSAGE = 'message';
    const STACK_TRACE = 'stack_trace';
    const CONSUMER = 'consumer';
    const EMAIL_SUBJECT = 'email_subject';
    const EMAIL_RECIPIENTS = 'email_recipients';
    const TIMESTAMP = 'timestamp';

    /**
     * @return string
     */
    public function getMessage();

    /**
     * @return string
     */
    public function getStackTrace();

    /**
     * @return string
     */
    public function getConsumer();

    /**
     * @return string
     */
    public function getEmailSubject();

    /**
     * @return string[]
     */
    public function getEmailRecipients();

    /**
     * @return int
     */
    public function getTimestamp();

    /**
     * @param string $message
     * @return \BlueAcorn\AmqpBase\Api\Data\AlertInterface
     */
    public function setMessage($message);

    /**
     * @param string $stackTrace
     * @return \BlueAcorn\AmqpBase\Api\Data\AlertInterface
     */
    public function setStackTrace($stackTrace);

    /**
     * @param string $consumer
     * @return \BlueAcorn\AmqpBase\Api\Data\AlertInterface
     */
    public function setConsumer($consumer);

    /**
     * @param string $emailSubject
     * @return \BlueAcorn\AmqpBase\Api\Data\AlertInterface
     */
    public function setEmailSubject($emailSubject);

    /**
     * @param string[] $emailRecipients
     * @return \BlueAcorn\AmqpBase\Api\Data\AlertInterface
     */
    public function setEmailRecipients($emailRecipients);

    /**
     * @param int $timestamp
     * @return \BlueAcorn\AmqpBase\Api\Data\AlertInterface
     */
    public function setTimestamp($timestamp);
}
