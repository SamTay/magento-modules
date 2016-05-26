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
 * TODO ADD docs explaining that arg names are important
 */
class Import implements ImportInterface
{
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
