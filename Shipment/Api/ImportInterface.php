<?php
/*
 * @package     BlueAcorn\AmqpShipment
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\AmqpShipment\Api;

interface ImportInterface
{
    const ENTITY_TYPE = 'shipment';

    /**
     * @param \Magento\Sales\Api\Data\ShipmentInterface[] $shipments
     */
    public function create(array $shipments);

    /**
     * @param \Magento\Sales\Api\Data\ShipmentInterface[] $shipments
     */
    public function update(array $shipments);

    /**
     * @param \Magento\Sales\Api\Data\ShipmentInterface[] $shipments
     */
    public function delete(array $shipments);
}
