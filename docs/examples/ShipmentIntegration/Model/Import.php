<?php
/*
 * @package     BlueAcorn\AmqpProduct
 * @version     0.2.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\ShipmentIntegration\Model;

use BlueAcorn\ShipmentIntegration\Api\ImportInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;

/**
 * Class Import
 * TODO ADD docs explaining that arg names are important
 */
class Import implements ImportInterface
{
    /**
     * @var ShipmentRepositoryInterface
     */
    private $shipmentRepository;

    /**
     * Import constructor.
     * @param ShipmentRepositoryInterface $shipmentRepository
     */
    public function __construct(ShipmentRepositoryInterface $shipmentRepository)
    {
        $this->shipmentRepository = $shipmentRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $shipments)
    {
        foreach($shipments as $shipment) {
            $this->shipmentRepository->save($shipment);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function update(array $shipments)
    {
        foreach($shipments as $shipment) {
            $this->shipmentRepository->save($shipment);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function delete(array $shipments)
    {
        foreach($shipments as $shipment) {
            $this->shipmentRepository->delete($shipment);
        }
    }
}
