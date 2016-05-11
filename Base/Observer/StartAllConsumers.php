<?php
/**
 * @package     BlueAcorn\AmqpBase
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\AmqpBase\Observer;

use BlueAcorn\AmqpBase\Model\Consumer\Daemonizer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Event\Observer as EventObserver;

class StartAllConsumers implements ObserverInterface
{
    /**
     * @var Daemonizer
     */
    protected $daemonizer;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * StartAllConsumers constructor.
     * @param Daemonizer $daemonizer
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        Daemonizer $daemonizer,
        ManagerInterface $messageManager
    ) {
        $this->daemonizer = $daemonizer;
        $this->messageManager = $messageManager;
    }

    /**
     * Delegate to daemonizer to start up the consumers according to the current configuration
     *
     * @param EventObserver $observer
     */
    public function execute(EventObserver $observer)
    {
        $statuses = $this->daemonizer->startAllConsumers();
        if (in_array(Daemonizer::STATUS_TRUNCATE_STARTED, $statuses)) {
            $this->messageManager->addNotice(
                'Some daemons needed to be removed for this configuration. Because this requires'
                . ' daemons that are "free", i.e., not currently consuming important messages, this can take'
                . ' a few minutes.'
            );
        }
    }
}
