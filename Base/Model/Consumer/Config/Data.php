<?php
/**
 * @package     BlueAcorn\AmqpBase
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\AmqpBase\Model\Consumer\Config;

use Magento\Framework\Config\CacheInterface;

/**
 * Class Data
 * Holds configuration for consumers
 */
class Data extends \Magento\Framework\Config\Data
{
    /**
     * Initialize parameters
     *
     * @param Reader $reader
     * @param CacheInterface $cache
     * @param string $cacheId
     */
    public function __construct(
        Reader $reader,
        CacheInterface $cache,
        $cacheId = 'ba_amqp_config_cache'
    ) {
        parent::__construct($reader, $cache, $cacheId);
    }

    /**
     * Merge consumers and return
     *
     * @param string $consumerName
     * @return array
     */
    public function getConsumerConfigByName($consumerName)
    {
        return $this->get()[$consumerName];
    }
}
