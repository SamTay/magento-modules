<?php
/**
 * @package     BlueAcorn\EntityMap
 * @version     0.2.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\EntityMap;

use Magento\Framework\ObjectManagerInterface;

class MapperFactory
{
    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var MapperInterface[]
     */
    protected $pool = [];

    /**
     * MapperFactory constructor.
     *
     * @param ObjectManagerInterface $objectManager
     * @param MapperInterface[] $pool
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        array $pool = []
    ) {
        $this->objectManager = $objectManager;
        $this->pool = $pool;
    }

    /**
     * Only implementing "get", as all mappers should be sharable within entity types
     *
     * @param $mapperClass
     * @throws \InvalidArgumentException
     * @return MapperInterface
     */
    public function get($mapperClass)
    {
        if (!isset($this->pool[$mapperClass])) {
            $mapperInstance = $this->objectManager->create($mapperClass);
            if (!$mapperInstance instanceof MapperInterface) {
                throw new \InvalidArgumentException(get_class($mapperInstance) . ' isn\'t instance of MapperInterface');
            }
            $this->pool[$mapperClass] = $mapperInstance;
        }

        return $this->pool[$mapperClass];
    }
}
