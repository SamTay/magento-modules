<?php
/**
 * @package     BlueAcorn\Core
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\Core\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;

/**
 * Class ScopeConfig
 * Publicly exposes system configuration for convenient use in templates
 *
 * @method mixed getValue($path, $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeCode = null)
 * @method bool isSetFlag($path, $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeCode = null)
 */
class ScopeConfig extends AbstractHelper
{
    /**
     * Forward methods to $scopeConfig
     *
     * @param $method
     * @param $arguments
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __call($method, $arguments)
    {
        if (is_callable([$this->scopeConfig, $method])) {
            return call_user_func_array([$this->scopeConfig, $method], $arguments);
        }
        throw new \Magento\Framework\Exception\LocalizedException(
            new \Magento\Framework\Phrase('Invalid method %1::%2(%3)', [get_class($this), $method, print_r($arguments, 1)])
        );
    }
}