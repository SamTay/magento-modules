<?php
/**
 * @package     BlueAcorn\EntityMap
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\EntityMap\Mapper;

use BlueAcorn\EntityMap\MapperInterface;

class OneToOne implements MapperInterface
{
    /**
     * {@inheritdoc}
     */
    public function map($key, $value)
    {
        return [$key, $value];
    }
}
