<?php
/**
 * @package     BlueAcorn\AmqpShipping
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\AmqpShipping\Model;

use Magento\Sales\Api\Data\ShipmentInterface;

class Processor
{
    /**
     * TODO: Remove this! Just testing the consumer out
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * Processor constructor.
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(\Psr\Log\LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param ShipmentInterface $message
     */
    public function processMessage(ShipmentInterface $message)
    {
        $this->logger->debug('Hit AmqpShiping Processor !');
        $this->logger->debug(var_export($message, true));
    }
}
