<?php
/**
 * @package     BlueAcorn\AmqpBase
 * @version     0.2.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\AmqpBase\Model\Shell;

use Magento\Framework\OsInfo;
use Magento\Framework\Shell\CommandRenderer;

class CommandRendererBackground extends CommandRenderer
{
    const DEFAULT_PIPE = '/dev/null';

    /**
     * @var string
     */
    protected $pipeDestination = self::DEFAULT_PIPE;

    /**
     * @param OsInfo $osInfo
     * @param string $pipeDestination
     */
    public function __construct(
        OsInfo $osInfo,
        $pipeDestination = null
    ) {
        $this->osInfo = $osInfo;
        $this->pipeDestination = $pipeDestination ? $this->absolutePath($pipeDestination) : self::DEFAULT_PIPE;
    }

    /**
     * Render command with arguments
     *
     * @param string $command
     * @param array $arguments
     * @return string
     */
    public function render($command, array $arguments = [])
    {
        $command = parent::render($command, $arguments);
        return $this->osInfo->isWindows() ?
            'start /B "magento background task" ' . $command
            : 'nohup ' . $command . ' > ' . $this->pipeDestination . ' 2>&1 &';
    }

    /**
     * Get absolute server path from a webroot relative path
     *
     * @param $relativePath
     * @return string
     */
    protected function absolutePath($relativePath)
    {
        return BP . $relativePath;
    }
}
