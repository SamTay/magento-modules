<?php
/**
 * @package     BlueAcorn\AmqpBase
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\AmqpBase\Plugin\System\Config\Structure;

use Magento\Framework\Api\SimpleDataObjectConverter;
use Magento\Framework\MessageQueue\Config\Data as QueueConfig;
use Magento\Framework\MessageQueue\Config\Converter as QueueConverter;
use BlueAcorn\AmqpBase\Helper\Consumer\Config as ConsumerHelper;

/**
 * Class Converter
 * Plugin that fills in system configuration groups for each consumer
 */
class Converter
{
    const SECTION = ConsumerHelper::SECTION;
    const GROUP = ConsumerHelper::GROUP;
    const GROUP_TEMPLATE = 'template';

    /**
     * @var QueueConfig
     */
    protected $queueConfig;

    /**
     * @param QueueConfig $queueConfig
     */
    public function __construct(QueueConfig $queueConfig)
    {
        $this->queueConfig = $queueConfig;
    }

    /**
     * Modify system configuration for amqp consumers group
     *
     * @param \Magento\Config\Model\Config\Structure\Converter $subject
     * @param array $result
     *
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function afterConvert(\Magento\Config\Model\Config\Structure\Converter $subject, array $result)
    {
        $groupIterator = 0;
        if (!isset($result['config']['system']['sections'][self::SECTION]['children'][self::GROUP]['children'][self::GROUP_TEMPLATE])) {
            return $result;
        }
        // Iterate over template group in system.xml with consumers (registered in queue.xml)
        foreach($this->getConsumerList() as $groupId => $groupLabel) {
            $template = $result['config']['system']['sections'][self::SECTION]['children'][self::GROUP]['children'][self::GROUP_TEMPLATE];
            $template['id'] = $groupId;
            $template['label'] .= $groupLabel;
            $template['sortOrder'] += $groupIterator++;

            $fieldIterator = 0;
            // Specify template fields to particular group
            foreach($template['children'] as $fieldName => &$fieldProperties) {
                $fieldProperties['path'] = self::SECTION . '/' . self::GROUP . '/' . $groupId;
                $fieldProperties['sortOrder'] += $fieldIterator++;
            }
            $result['config']['system']['sections'][self::SECTION]['children'][self::GROUP]['children'][$groupId] = $template;
        }
        // Remove the empty template from system config
        unset($result['config']['system']['sections'][self::SECTION]['children'][self::GROUP]['children'][self::GROUP_TEMPLATE]);
        return $result;
    }

    /**
     * Get list of consumers
     *
     * @return array
     */
    protected function getConsumerList()
    {
        $list = [];
        $consumers = $this->queueConfig->get(QueueConverter::CONSUMERS, []);
        foreach($consumers as $consumer) {
            $consumerName = $consumer[QueueConverter::CONSUMER_NAME];
            $list[$consumerName] = $this->camelCaseToLabel($consumerName);
        }

        return $list;
    }

    /**
     * Get human readable label from consumer name
     * (Note this assumes adhering to convention on camel casing consumer names!)
     *
     * @param string $camelCase
     * @return string
     */
    protected function camelCaseToLabel($camelCase)
    {
        $snakeCase = SimpleDataObjectConverter::camelCaseToSnakeCase($camelCase);
        return ucwords(str_replace('_', ' ', $snakeCase));
    }
}
