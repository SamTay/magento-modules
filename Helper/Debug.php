<?php
/**
 * @package     BlueAcorn\ContentPublisher
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\ContentPublisher\Helper;

use Psr\Log\LoggerInterface;

class Debug
{
    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * Flag to debug or not
     * @var bool
     */
    protected $_debugMode = false;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->_logger = $logger;
    }

    /**
     * Log message if in debug mode
     *
     * @param $msg
     */
    public function log($msg)
    {
        if ($this->_debugMode) {
            $this->_logger->info($msg); // For some reason $_logger->debug is not working
        }
    }
}