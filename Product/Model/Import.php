<?php
/*
 * @package     BlueAcorn\AmqpProduct
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\AmqpProduct\Model;

use BlueAcorn\AmqpBase\Helper\LogManager;
use BlueAcorn\AmqpProduct\Api\ImportInterface;
use BlueAcorn\EntityMap\Decoder;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;

class Import implements ImportInterface
{
    /**
     * TODO: This will need to be defined at integration level, not sure where
     * TODO: Possibly add configuration (via XML or system config) for higher level schema
     */
    const PRODUCTS_KEY = 'products';

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
        array_walk($products, function($productData) {
            $product = $this->decode($productData);
            $this->productRepository->save($product);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function update(array $products)
    {
        $this->logManager->getLogger('product')->debug(var_export($products[0]->toArray()));
        array_walk($products, function($productData) {
            $product = $this->decode($productData);
            $this->productRepository->save($product);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function delete(array $products)
    {
        array_walk($products, function($productData) {
            // Decode here in case identifier is mapped (e.g. 'PLU' => 'sku')
            $product = $this->decode($productData);
            $this->productRepository->delete($product);
        });
    }

    /**
     * Decode product
     *
     * @param mixed $product
     * @return ProductInterface
     */
    protected function decode($product)
    {
        return $this->decoder->convert($product, self::ENTITY_TYPE);
    }
}
