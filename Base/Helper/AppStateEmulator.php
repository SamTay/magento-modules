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
     * Import constructor.
     * @param AppState $appState
     */
    public function __construct(
        AppState $appState
    ) {
        $this->appState = $appState;
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
        return $this->appState->emulateAreaCode($this->areaCode, $closure, $args);
    }
}
