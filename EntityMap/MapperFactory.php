<?php
/**
 * @package     BlueAcorn\EntityMap
 * @version     1.0.0
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
     */
    public function __construct(
        ObjectManagerInterface $objectManager
    ) {
        $this->objectManager = $objectManager;
    }

    /**
     * Only implementing "get", as all mappers should be sharable within entity types
     *
     * @param $mapperClass
     * @param $entityType
     * @throws \InvalidArgumentException
     * @return MapperInterface
     */
    public function get($mapperClass, $entityType = null)
    {
        $entityKey = $entityType ?: 'shared';
        $poolKey = $entityKey . '|' . $mapperClass;
        if (!isset($this->pool[$poolKey])) {
            $mapperInstance = $this->objectManager->create(
                $mapperClass,
                // TODO reflection here to see if constructor takes entityType
                $entityType ? ['entityType' => $entityType] : []
            );
            if (!$mapperInstance instanceof MapperInterface) {
                throw new \InvalidArgumentException(get_class($mapperInstance) . ' isn\'t instance of MapperInterface');
            }
            $this->pool[$poolKey] = $mapperInstance;
        }

        return $this->pool[$poolKey];
    }
}
