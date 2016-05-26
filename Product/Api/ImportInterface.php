<?php
/*
 * @package     BlueAcorn\AmqpProduct
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\AmqpProduct\Api;

use Magento\Catalog\Model\Product;

interface ImportInterface
{
    /**
     * @param \Magento\Catalog\Api\Data\ProductInterface[] $products
     */
    public function create(array $products);

    /**
     * @param \Magento\Catalog\Api\Data\ProductInterface[] $products
     */
    public function update(array $products);

    /**
     * @param \Magento\Catalog\Api\Data\ProductInterface[] $products
     */
    public function delete(array $products);
}
