<?php
/**
 * @package     BlueAcorn\EntityMap
 * @version     0.2.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\EntityMap\Webapi;

class ServiceOutputProcessor extends \Magento\Framework\Webapi\ServiceOutputProcessor
{
    /**
     * {@inheritdoc}
     */
    public function convertValue($data, $type)
    {
        return parent::convertValue($data, $type);
    }
}
