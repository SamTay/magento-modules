<?php
/*
 * @package     BlueAcorn\AmqpProduct
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\AmqpProduct\Api;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;

interface ImportInterface
{
    const ENTITY_TYPE = Product::ENTITY;

    /**
     */
    public function create(array $products);

    /**
     * TESTING TODO REMOVE
     * @param \Magento\Catalog\Api\Data\ProductInterface[] $products
     */
    public function update(array $products);

    /**
     */
    public function delete(array $products);
}
