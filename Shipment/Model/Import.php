<?php
/*
 * @package     BlueAcorn\AmqpProduct
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\AmqpShipment\Model;

use BlueAcorn\AmqpBase\Helper\LogManager;
use BlueAcorn\AmqpShipment\Api\ImportInterface;
use BlueAcorn\EntityMap\Converter as EntityConverter;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;

/**
 * Class Import
 * TODO Fix phpdocs & arguments
 * TODO Add "metadata" with repository & entity converter type
 * TODO Add abstract class for importers
 */
class Import implements ImportInterface
{
    /**
     * TODO: This will need to be defined at integration level, not sure where
     * TODO: Possibly add configuration (via XML or system config) for higher level schema
     */
    const DATA_KEY = 'shipments';

    /**
     * {@inheritdoc}
     */
    public function create(array $products)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function update(array $products)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function delete(array $products)
    {
    }
}
