<?php
/**
 * @package     BlueAcorn\AmqpBase
 * @version     0.2.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\AmqpBase\Exception;

/**
 * Class ConnectionLostException
 *
 * WARNING: This is a rare scenario and can lead to infinite message loops,
 * only use this exception type when certain that a message is ready to be reconsumed
 */
class ConsumptionUnfinishedException extends \Exception
{
}
