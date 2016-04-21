<?php
/**
 * @package     BlueAcorn\Core
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\Core\Model\Logger\Handler;

use Monolog\Logger;

class Base extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * Logging level
     * Choosing lowest level for top priority handler
     * @var int
     */
    protected $loggerType = Logger::DEBUG;

    /**
     * File name
     * Generic 'ba.log' filename should be modified by children/virtual classes
     * @var string
     */
    protected $fileName = '/var/log/ba.log';

    /**
     * @param DriverInterface $filesystem
     * @param null|string $filePath
     * @param string $fileName
     */
    public function __construct(
        DriverInterface $filesystem,
        $filePath = null,
        $fileName = ''
    ) {
        if ($fileName) {
            $this->fileName = $fileName;
        }
        parent::__construct($filesystem, $filePath);
    }
}
