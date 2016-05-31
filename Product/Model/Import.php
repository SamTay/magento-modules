<?php
/*
 * @package     BlueAcorn\AmqpProduct
 * @version     0.2.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\AmqpProduct\Model;

use BlueAcorn\AmqpBase\Helper\LogManager;
use BlueAcorn\AmqpProduct\Api\ImportInterface;
use BlueAcorn\EntityMap\Decoder;
use Magento\Catalog\Api\ProductRepositoryInterface;

class Import implements ImportInterface
{
    /**
     * @var Decoder
     */
    protected $decoder;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var LogManager
     */
    protected $logManager;

    /**
     * Import constructor.
     * @param Decoder $decoder
     * @param ProductRepositoryInterface $productRepository
     * @param LogManager $logManager
     */
    public function __construct(
        Decoder $decoder,
        ProductRepositoryInterface $productRepository,
        LogManager $logManager
    ) {
        $this->decoder = $decoder;
        $this->productRepository = $productRepository;
        $this->logManager = $logManager;
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $products)
    {
        foreach($products as $product) {
            $this->productRepository->save($product);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function update(array $products)
    {
        $this->logManager->getLogger('product')->debug(var_export($products[0]->toArray()));
        foreach($products as $product) {
            $this->productRepository->save($product);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function delete(array $products)
    {
        foreach($products as $product) {
            // standard `delete` method requires entity ID on $product
            $this->productRepository->deleteById($product->getSku());
        }
    }
}
