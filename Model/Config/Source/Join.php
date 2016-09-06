<?php
/**
 * @package     BlueAcorn\LayeredNavigation
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\LayeredNavigation\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\DB\Select;

class Join implements OptionSourceInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => Select::SQL_AND, 'label' => __('And')],
            ['value' => Select::SQL_OR, 'label' => __('Or')]
        ];
    }
}
