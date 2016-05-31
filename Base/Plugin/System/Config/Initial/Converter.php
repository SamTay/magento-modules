<?php
/**
 * @package     BlueAcorn\AmqpBase
 * @version     0.2.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\AmqpBase\Plugin\System\Config\Initial;

use BlueAcorn\AmqpBase\Model\Consumer\Config\Data as ConsumerConfig;
use BlueAcorn\AmqpBase\Plugin\System\Config\Structure\Converter as StructureConverter;
/**
 * Class Converter
 * Plugin that sets up default values for each consumer system configuration group
 */
class Converter
{
    /**
     * @var ConsumerConfig
     */
    protected $consumerConfig;

    /**
     * Converter constructor.
     * @param ConsumerConfig $consumerConfig
     */
    public function __construct(ConsumerConfig $consumerConfig)
    {
        $this->consumerConfig = $consumerConfig;
    }

    /**
     * Modify global configuration for amqp consumers
     *
     * @param \Magento\Framework\App\Config\Initial\Converter $subject
     * @param array $result
     *
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterConvert(\Magento\Framework\App\Config\Initial\Converter $subject, array $result)
    {
        $result['data']['default'][StructureConverter::SECTION][StructureConverter::GROUP] = $this->consumerConfig->get();
        return $result;
    }
}
