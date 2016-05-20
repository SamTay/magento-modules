<?php
/**
 * @package     BlueAcorn\EntityMap
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\EntityMap\Mapper;

use BlueAcorn\EntityMap\MapperInterface;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Model\Entity\Attribute\Source\Table as OptionTable;

class LabelToOption implements MapperInterface
{
    /**
     * @var EavConfig
     */
    protected $config;

    /**
     * @var OptionTable
     */
    protected $source;

    /**
     * LabelToOption constructor.
     * TODO: Shouldn't be specific to EavConfig. If this is necessary then move class to Eav folder
     * @param EavConfig $config
     */
    public function __construct(EavConfig $config, $entityType)
    {
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function map($key, $value)
    {
        $this->setAttribute($key);
        $optionLabels = explode(',', $value);
        $optionIds = array_reduce($optionLabels, function($carry, $label) {
            return $carry . $this->source->getOptionId($label);
        }, '');

        return [$key, $optionIds];
    }

    /**
     * Sets attribute on source model
     *
     * @param $attributeCode
     */
    protected function setAttribute($attributeCode)
    {
        // TODO: THESE CLASSES SHOULD NOT HANDLE ENTITY SPECIFICS, BUT SHOULD BE DECORATED TO DO SO!
        // Somehow knows $entityType
        // Throws exception if not found

        $attribute = $this->config->getAttribute($entityType, $attributeCode);
        $this->source->setAttribute($attribute);
    }
}
