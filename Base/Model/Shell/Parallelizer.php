<?php
/**
 * @package     BlueAcorn\AmqpBase
 * @version     0.2.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\AmqpBase\Model\Shell;

use Magento\Framework\Phrase;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ShellInterface;

/**
 * Shell command line wrapper for background tasks
 */
class Parallelizer implements ShellInterface
{
    /**
     * Logger instance
     *
     * @var \Zend_Log
     */
    protected $logger;

    /**
     * @var CommandRendererBackground
     */
    protected $commandRenderer;

    /**
     * @param CommandRendererBackground $commandRenderer
     * @param \Zend_Log $logger Logger instance to be used to log commands and their output
     */
    public function __construct(
        CommandRendererBackground $commandRenderer,
        \Zend_Log $logger = null
    ) {
        $this->logger = $logger;
        $this->commandRenderer = $commandRenderer;
    }

    /**
     * Execute a command through the command line, passing properly escaped arguments
     * and continue php execution in parallel
     *
     * @param string $command Command with optional argument markers '%s'
     * @param string[] $arguments Argument values to substitute markers with
     * @throws LocalizedException
     * @return void
     */
    public function execute($command, array $arguments = [])
    {
        $command = $this->commandRenderer->render($command, $arguments);
        $this->log($command);

        $disabled = explode(',', str_replace(' ', ',', ini_get('disable_functions')));
        if (in_array('proc_open', $disabled) || in_array('proc_close', $disabled)) {
            throw new LocalizedException(new Phrase("proc_open or proc_closed function is disabled."));
        }

        proc_close(proc_open($command, [], $dummy));
    }

    /**
     * Log a message, if a logger is specified
     *
     * @param string $message
     * @return void
     */
    protected function log($message)
    {
        if ($this->logger) {
            $this->logger->log($message, \Zend_Log::INFO);
        }
    }
}
