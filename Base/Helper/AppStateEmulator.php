<?php
/*
 * @package     BlueAcorn\AmqpBase
 * @version     0.2.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\AmqpBase\Helper;

use Magento\Framework\App\State as AppState;
use Magento\Backend\App\Area\FrontNameResolver as BackendArea;
use Magento\Framework\Registry;

/**
 * Class AppStateEmulator
 * Convenient emulation of area
 */
class AppStateEmulator
{
    /**
     * @var AppState
     */
    protected $appState;

    /**
     * @var string
     */
    protected $areaCode = BackendArea::AREA_CODE;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var bool
     */
    protected $prevSecureValue = false;

    /**
     * Import constructor.
     * @param AppState $appState
     * @param Registry $registry
     */
    public function __construct(
        AppState $appState,
        Registry $registry
    ) {
        $this->appState = $appState;
        $this->registry = $registry;
    }

    /**
     * Set area code
     *
     * @param $areaCode
     * @return $this
     */
    public function setAreaCode($areaCode)
    {
        $this->areaCode = $areaCode;
        return $this;
    }

    /**
     * Set secure area
     * Removes secure area setting after 'wrap' is called
     *
     * @param bool $secure
     * @return $this
     */
    public function setSecureArea($secure = true)
    {
        $this->prevSecureValue = $this->registry->registry('isSecureArea') ?: false;
        $this->registry->unregister('isSecureArea');
        $this->registry->register('isSecureArea', $secure);
        return $this;
    }

    /**
     * Wrap function in an emulated area.
     * Accepts variable number of arguments to pass to the $closure argument
     *
     * @param \Closure $closure
     * @throws \Exception
     * @return mixed
     */
    public function wrap(\Closure $closure)
    {
        $args = func_get_args(); // Get variable number of arguments
        array_shift($args); // Remove first \Closure argument
        $return = $this->appState->emulateAreaCode($this->areaCode, $closure, $args);
        $this->registry->unregister('isSecureArea');
        $this->registry->register('isSecureArea', $this->prevSecureValue);
        return $return;
    }
}
