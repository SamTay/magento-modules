<?php
/**
 * @package     BlueAcorn\EntityMap
 * @version     0.2.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\EntityMap\Model\Mapper;

use BlueAcorn\EntityMap\Model\Escape;
use BlueAcorn\EntityMap\Model\MapperInterface;
use Magento\Framework\Data\OptionSourceInterface;

class LabelToOption implements MapperInterface
{
    /**
     * @var OptionSourceInterface
     */
    protected $source;

    /**
     * {@inheritdoc}
     */
    public function map($key, $value)
    {
        if (!$this->source) {
            throw new \LogicException(__(
                'Source must be set on "%class" before `map` is called',
                ['class' => get_class($this)]
            ));
        }
        $optionLabels = Escape::_explode($value);
        $optionValues = array_filter(array_map([$this, 'getOptionValue'], $optionLabels));
        return [$key => implode(',', $optionValues)];
    }

    /**
     * Set source model
     *
     * @param OptionSourceInterface $source
     * @return $this
     */
    public function setSource(OptionSourceInterface $source)
    {
        $this->source = $source;
        return $this;
    }

    /**
     * Get value from label
     *
     * @param $label
     * @return string|int|null
     */
    public function getOptionValue($label)
    {
        foreach($this->source->toOptionArray() as $option) {
            if ($option['label'] == $label) {
                return $option['value'];
            }
        }
        return null;
    }
}
