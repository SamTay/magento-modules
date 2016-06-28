<?php
/**
 * @package     BlueAcorn\EntityMap
 * @version     0.2.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\EntityMap\Model;

/**
 * Class MapperDecorator
 * Abstract decorator class for mappers, as these objects might be better suited
 * to decoration rather than inheritance
 */
abstract class MapperDecorator implements MapperInterface
{
    /**
     * @var MapperInterface
     */
    protected $mapper;

    /**
     * MapperDecorator constructor.
     * @param MapperInterface $mapper
     */
    public function __construct(MapperInterface $mapper)
    {
        $this->mapper = $mapper;
    }

    /**
     * {@inheritdoc}
     */
    abstract public function map($key, $value);
}
