<?php
/**
 * @package     BlueAcorn\EntityMap
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\EntityMap\Config;

use Magento\Framework\Config\CacheInterface;

/**
 * Class for access to EntityMap configuration.
 */
class Data extends \Magento\Framework\Config\Data
{
    /**
     * Initialize dependencies.
     *
     * @param Reader $reader
     * @param CacheInterface $cache
     * @param string $cacheId
     */
    public function __construct(
        Reader $reader,
        CacheInterface $cache,
        $cacheId = 'ba_entity_map_config_cache'
    ) {
        parent::__construct($reader, $cache, $cacheId);
    }
}
