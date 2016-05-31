<?php
/**
 * @package     BlueAcorn\AmqpBase
 * @version     0.2.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\AmqpBase\Observer;

use BlueAcorn\AmqpBase\Model\Consumer\Daemonizer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Event\Observer as EventObserver;

class ConfigSectionSave implements ObserverInterface
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
        $status = $this->daemonizer->startAllConsumers();
        if ($status[Daemonizer::STATUS_TRUNCATE_NECESSARY]) {
            $this->messageManager->addNotice(
                'These changes require removing consumer daemons. Because this requires consumers that are "free",'
                . ' that is, not currently consuming important messages, this may take a few minutes.'
            );
        }
    }
}
