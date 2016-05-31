<?php
/**
 * @package     BlueAcorn\AmqpBase
 * @version     0.2.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\AmqpBase\Model;

use Magento\Framework\MessageQueue\Config\Converter as QueueConfigConverter;
use BlueAcorn\AmqpBase\Helper\MessageQueue\Config as QueueConfig;
use Magento\Framework\MessageQueue\ConsumerFactory;
use Magento\Framework\Exception\LocalizedException;

/**
 * *************************************************************************
 * Rewritten to respect max_messages and avoid excessive daemon connections
 * [Revisit after EE upgrades to check on original model]
 * @see \Magento\MessageQueue\Model\ConsumerRunner
 * *************************************************************************
 *
 * Consumer runner class is used to run consumer, which name matches the magic method invoked on this class.
 *
 * Is used to schedule consumers execution in crontab.xml as follows:
 * <job name="consumerConsumerName" instance="Magento\MessageQueue\Model\ConsumerRunner" method="consumerName">
 * Where <i>consumerName</i> should be a valid name of consumer registered in some queue.xml
 */
class ConsumerRunner
{
    /**
     * @var ConsumerFactory
     */
    protected $consumerFactory;

    /**
     * @var QueueConfig
     */
    protected $queueConfig;

    /**
     * Initialize dependencies.
     *
     * @param ConsumerFactory $consumerFactory
     * @param QueueConfig $queueConfig
     */
    public function __construct(
        ConsumerFactory $consumerFactory,
        QueueConfig $queueConfig
    ) {
        $this->consumerFactory = $consumerFactory;
        $this->queueConfig = $queueConfig;
    }

    /**
     * Process messages in queue using consumer, which name is equal to the current magic method name.
     *
     * @param string $name
     * @param array $arguments
     * @throws LocalizedException
     * @return void
     */
    public function __call($name, $arguments)
    {
        try {
            $consumer = $this->consumerFactory->get($name);
            $maxMessages = $this->queueConfig->getMaxMessages($name);
        } catch (\Exception $e) {
            $errorMsg = '"%callbackMethod" callback method specified in crontab.xml '
                . 'must have corresponding consumer declared in some queue.xml.';
            throw new LocalizedException(__($errorMsg, ['callbackMethod' => $name]));
        }
        $consumer->process($maxMessages);
    }
}
