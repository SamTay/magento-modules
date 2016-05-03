<?php
/**
 * @package     BlueAcorn\Core
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\Core\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

/**
 * Class ModuleManager
 * Exposes module manager for convenient usage in template files
 *
 * @method bool isEnabled($moduleName)
 * @method bool isOutputEnabled($moduleName)
 */
class ModuleManager extends AbstractHelper
{
    /**
     * Forward methods to $_moduleManager
     *
     * @param $method
     * @param $arguments
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __call($method, $arguments)
    {
        if (is_callable([$this->_moduleManager, $method])) {
            return call_user_func_array([$this->_moduleManager, $method], $arguments);
        }
        throw new \Magento\Framework\Exception\LocalizedException(
            new \Magento\Framework\Phrase('Invalid method %1::%2(%3)', [get_class($this), $method, print_r($arguments, 1)])
        );
    }
}
