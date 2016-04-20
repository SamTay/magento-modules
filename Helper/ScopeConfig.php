<?php
/**
 * @package     BlueAcorn\Core
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\Core\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Class ScopeConfig
 * Publicly exposes system configuration for convenient use in templates
 *
 * @method mixed getValue($path, $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeCode = null)
 * @method bool isSetFlag($path, $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeCode = null)
 */
class ScopeConfig
{
    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->_scopeConfig = $scopeConfig;
    }

    /**
     * Forward methods to $_scopeConfig
     *
     * @param $method
     * @param $arguments
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __call($method, $arguments)
    {
        if (method_exists($this->_scopeConfig, $method)) {
            return call_user_func_array([$this->_scopeConfig, $method], $arguments);
        }
        throw new \Magento\Framework\Exception\LocalizedException(
            new \Magento\Framework\Phrase('Invalid method %1::%2(%3)', [get_class($this), $method, print_r($arguments, 1)])
        );
    }
}