<?php
/**
 * @package     BlueAcorn\AmqpBase
 * @version     0.2.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\AmqpBase\Plugin\System\Config\Structure;

use BlueAcorn\AmqpBase\Model\Consumer\Daemonizer;
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
        $template = $result['config']['system']['sections'][self::SECTION]['children'][self::GROUP]['children'][self::GROUP_TEMPLATE];
        $this->addDaemonCountLimiter($template);

        // Iterate over template group in system.xml with consumers (registered in queue.xml)
        foreach($this->getConsumerList() as $groupId => $groupLabel) {
            $group = $template;
            $group['id'] = $groupId;
            $group['label'] .= $groupLabel;
            $group['sortOrder'] += $groupIterator++;

            $fieldIterator = 0;
            // Specify template fields to particular group
            foreach($group['children'] as $fieldName => &$fieldProperties) {
                $fieldProperties['path'] = self::SECTION . '/' . self::GROUP . '/' . $groupId;
                $fieldProperties['sortOrder'] += $fieldIterator++;
            }
            $result['config']['system']['sections'][self::SECTION]['children'][self::GROUP]['children'][$groupId] = $group;
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
     * Add daemon count range validation to the template by reference
     *
     * @param $template
     */
    protected function addDaemonCountLimiter(&$template)
    {
        if (!isset($template['children'][ConsumerHelper::FIELD_DAEMON_COUNT])
            || !isset($template['children'][ConsumerHelper::FIELD_DAEMON_COUNT]['validate'])
        ) {
            return;
        }
        $currentValidation = explode(' ', $template['children'][ConsumerHelper::FIELD_DAEMON_COUNT]['validate']);
        $additionalValidation = [
            'validate-digits-range',
            'digits-range-0-' . Daemonizer::MAX_DAEMON_COUNT
        ];
        $template['children'][ConsumerHelper::FIELD_DAEMON_COUNT]['validate'] = implode(' ',
            array_unique(array_merge($currentValidation, $additionalValidation))
        );
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
