<?php
/*
 * @package     BlueAcorn\AmqpProduct
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\AmqpProduct\Model;

use BlueAcorn\EntityMap\Converter as EntityConverter;
use Magento\Catalog\Model\Product;

class Import
{
    const PRODUCTS_KEY = 'products'; // TODO: This will need to be defined at integration level, not sure where

    const ENTITY_TYPE = Product::ENTITY;

    /**
     * @var EntityConverter
     */
    protected $entityConverter;

    /**
     * Import constructor.
     * @param EntityConverter $entityConverter
     */
    public function __construct(
        EntityConverter $entityConverter
    ) {
        $this->entityConverter = $entityConverter;
    }

    /**
     * @param mixed[]
     */
    public function create(array $products)
    {
        $products = $this->entityConverter->decode($products[self::PRODUCTS_KEY], self::ENTITY_TYPE);
        // do some mass creation
    }

    public function update(array $products)
    {
        $products = $this->entityConverter->decode($products[self::PRODUCTS_KEY], self::ENTITY_TYPE);
        // do some mass updates
    }
}
