<?php
/**
 * @package     BlueAcorn\AmqpBase
 * @version     0.2.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\AmqpBase\Model;

use BlueAcorn\AmqpBase\Api\Data\AlertInterface;
use Magento\Framework\MessageQueue\PublisherFactory;

class AlertManager
{
    const ALERT_TOPIC = 'alert';

    /**
     * @var AlertBuilder
     */
    protected $alertBuilder;

    /**
     * @var PublisherFactory
     */
    protected $publisherFactory;

    /**
     * AlertManager constructor.
     * @param AlertBuilder $alertBuilder
     * @param PublisherFactory $publisherFactory
     */
    public function __construct(
        AlertBuilder $alertBuilder,
        PublisherFactory $publisherFactory
    ) {
        $this->alertBuilder = $alertBuilder;
        $this->publisherFactory = $publisherFactory;
    }

    /**
     * Get builder
     *
     * @return AlertBuilder
     */
    public function getBuilder()
    {
        return $this->alertBuilder;
    }

    /**
     * Publish alert message to the alerts exchange (configured in queue.xml)
     *
     * @param AlertInterface $alert
     */
    public function publish(AlertInterface $alert)
    {
        $publisher = $this->publisherFactory->create(self::ALERT_TOPIC);
        $publisher->publish(self::ALERT_TOPIC, $alert);
    }
}
