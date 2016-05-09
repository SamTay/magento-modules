<?php
/**
 * @package     BlueAcorn\AmqpBase
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\AmqpBase\Helper;

use Psr\Log\LoggerInterface;

/**
 * Class Logger
 * Expose custom logger (to ba_amqp.log file)
 * TODO: Add debug functionality // level of logging in system config
 */
class Logger
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function __call($method, $arguments)
    {
        if (is_callable([$this->logger, $method])) {
            return call_user_func_array([$this->logger, $method], $arguments);
        }
        throw new \Magento\Framework\Exception\LocalizedException(
            new \Magento\Framework\Phrase('Invalid method %1::%2(%3)', [get_class($this), $method, print_r($arguments, 1)])
        );
    }
}
