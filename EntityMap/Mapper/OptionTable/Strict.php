<?php
/**
 * @package     BlueAcorn\EntityMap
 * @version     0.2.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\EntityMap\Mapper\OptionTable;

use BlueAcorn\EntityMap\Mapper\LabelToOption;
use BlueAcorn\EntityMap\MapperDecorator;
use Magento\Eav\Api\AttributeRepositoryInterface;

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
     * @var LabelToOption
     */
    protected $mapper;

    /**
     * @var string
     */
    protected $entityType;

    /**
     * @var AttributeRepositoryInterface
     */
    protected $attributeRepository;

    /**
     * Strict constructor.
     *
     * @param AttributeRepositoryInterface $attributeRepository
     * @param LabelToOption $mapper
     * @param string $entityType
     */
    public function __construct(
        AttributeRepositoryInterface $attributeRepository,
        LabelToOption $mapper,
        $entityType
    ) {
        $this->attributeRepository = $attributeRepository;
        $this->entityType = $entityType;
        parent::__construct($mapper);
    }

    /**
     * {@inheritdoc}
     */
    public function map($key, $value)
    {
        $attribute = $this->attributeRepository->get($this->entityType, $key);
        /**
         * Unfortunately going against implementation here, assuming instance of AbstractAttribute. This is because
         * of a disconnect between OptionSourceInterface and the Eav/Api interfaces, which never implement it. I still
         * want to keep the LabelToOption class abstract enough to handle EAV and non EAV, so here we are
         */
        $this->mapper->setSource($attribute->getSource());
        return $this->mapper->map($key, $value);
    }
}
