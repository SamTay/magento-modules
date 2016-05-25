<?php
/**
 * @package     BlueAcorn\EntityMap
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\EntityMap\Mapper\OptionTable;

use BlueAcorn\EntityMap\Mapper\LabelToOption;
use BlueAcorn\EntityMap\MapperDecorator;
use Magento\Eav\Model\Config as EavConfig;

/**
 * Class Strict
 * Strict options updates -- does not create unknown option values and will update
 * attribute value to remove any options not present in the "value" argument.
 *
 * Example use case: color multiselect
 * - current value = 'blue,green'
 * - input value = 'blue, red'
 * - output value = 'blue, red'
 */
class Strict extends MapperDecorator
{
    /**
     * @var EavConfig
     */
    protected $eavConfig;

    /**
     * @var LabelToOption
     */
    protected $mapper;

    /**
     * @var string
     */
    protected $entityType;

    /**
     * Strict constructor.
     *
     * @param EavConfig $eavConfig
     * @param LabelToOption $mapper
     * @param string $entityType
     */
    public function __construct(
        EavConfig $eavConfig,
        LabelToOption $mapper,
        $entityType
    ) {
        $this->eavConfig = $eavConfig;
        $this->entityType = $entityType;
        parent::__construct($mapper);
    }

    /**
     * {@inheritdoc}
     */
    public function map($key, $value)
    {
        $attribute = $this->eavConfig->getAttribute($this->entityType, $key);
        $this->mapper->setSource($attribute->getSource());
        return $this->mapper->map($key, $value);
    }
}
