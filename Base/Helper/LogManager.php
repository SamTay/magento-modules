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
 * Expose custom loggers
 */
class LogManager
{
    const BASE_LOGGER = 'base';

    /**
     * @var LoggerInterface[]
     */
    protected $loggers;

    /**
     * Logger constructor.
     * @param LoggerInterface[] $loggers
     */
    public function __construct(array $loggers)
    {
        if (!array_key_exists(self::BASE_LOGGER, $loggers)) {
            throw new \InvalidArgumentException('Base logger is required');
        }
        foreach($loggers as $name => $logger) {
            $this->addLogger($name, $logger);
        }
    }

    /**
     * Get logger by name
     *
     * @param $name
     * @return LoggerInterface
     */
    public function getLogger($name)
    {
        if (array_key_exists($name, $this->loggers)) {
            return $this->loggers[$name];
        }
        $this->loggers[self::BASE_LOGGER]->error(__(
            'Logger "%name" not found. Logging to base instead.',
            ['name' => $name]
        ));

        return $this->loggers[self::BASE_LOGGER];
    }

    /**
     * Add logger
     *
     * @param $name
     * @param LoggerInterface $logger
     */
    private function addLogger($name, LoggerInterface $logger)
    {
        $this->loggers[$name] = $logger;
    }
}
