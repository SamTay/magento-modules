<?php
/**
 * @package     BlueAcorn\AmqpBase
 * @version     0.2.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\AmqpBase\Cron;

use BlueAcorn\AmqpBase\Model\Consumer\Daemonizer;
use BlueAcorn\AmqpBase\Helper\MessageQueue\Config as QueueConfig;

/**
 * Class RefreshDaemon
 * Refreshes all consumer daemons
 */
class RefreshDaemon
{
    /**
     * @var Daemonizer
     */
    private $daemonizer;

    /**
     * @var QueueConfig
     */
    private $queueConfig;

    /**
     * RefreshDaemon constructor.
     * @param Daemonizer $daemonizer
     * @param QueueConfig $queueConfig
     */
    public function __construct(
        Daemonizer $daemonizer,
        QueueConfig $queueConfig
    ) {
        $this->daemonizer = $daemonizer;
        $this->queueConfig = $queueConfig;
    }

    /**
     * Refresh all consumer daemons
     */
    public function execute()
    {
        // Remove all daemons
        foreach($this->queueConfig->getConsumersList() as $consumerName) {
            $this->daemonizer->removeDaemons($consumerName, Daemonizer::MAX_DAEMON_COUNT);
        }

        // Restart daemons according to configuration
        $this->daemonizer->startAllConsumers();
    }
}
