<?php
/**
 * @package     BlueAcorn\AmqpBase
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\AmqpBase\Helper\Consumer;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;

/**
 * Class Config
 * Convenience methods around ScopeConfig
 *
 * Would really rather have this data be merged with Magento\Framework\MessageQueue\ConsumerConfiguration,
 * but unfortunately Magento does not adhere to their own interface abstraction in the ConsumerFactory.. so
 * it would require rewriting multiple files from that message queue framework, and I'd rather separate concerns
 */
class Config extends AbstractHelper
{
    const SECTION = 'ba_amqp';
    const GROUP = 'consumers';

    const FIELD_DAEMON_COUNT = 'daemon_count';
    const FIELD_EMAIL_RECIPIENTS = 'email_recipients';
    const FIELD_EMAIL_SUBJECT = 'email_subject';

    const DEFAULT_DAEMON_COUNT = 0;
    const DEFAULT_EMAIL_RECIPIENTS = [];
    const DEFAULT_EMAIL_SUBJECT = 'AMQP Consumer Error';

    /**
     * @var array
     */
    protected $config = [];

    /**
     * @var array
     */
    protected $defaults = [];

    /**
     * Get daemon count
     *
     * @param string $consumerName
     * @return int
     */
    public function getDaemonCount($consumerName)
    {
        return $this->getConsumerConfig($consumerName)[self::FIELD_DAEMON_COUNT];
    }

    /**
     * Get email recipients
     *
     * @param string $consumerName
     * @return array
     */
    public function getEmailRecipients($consumerName)
    {
        return $this->getConsumerConfig($consumerName)[self::FIELD_EMAIL_RECIPIENTS];
    }

    /**
     * Get email subject
     *
     * @param string $consumerName
     * @return string
     */
    public function getEmailSubject($consumerName)
    {
        return $this->getConsumerConfig($consumerName)[self::FIELD_EMAIL_SUBJECT];
    }

    /**
     * Get array of configuration values for consumer
     *
     * @param string $consumerName
     * @return array
     */
    public function getConsumerConfig($consumerName)
    {
        if (!$this->config) {
            $this->config = $this->scopeConfig->getValue(self::SECTION . '/' . self::GROUP);
            foreach($this->config as $name => &$config) {
                $config = array_replace($this->getDefaultFields(), $config);
                $this->parseEmailRecipients($config[self::FIELD_EMAIL_RECIPIENTS]);
            }
            unset($config); // Manually unsetting so nothing ends up referencing the property pointer
        }

        return isset($this->config[$consumerName]) ? $this->config[$consumerName] : $this->getDefaultFields();
    }

    /**
     * Get array of default field => value pairs
     * Note -- these are the lowest level defaults; priority from high to low:
     * System Config (db) -> XML (consumer.xml) -> self (constants above)
     *
     * @return array
     */
    protected function getDefaultFields()
    {
        if (!$this->defaults) {
            $reflectionClass = new \ReflectionClass($this);
            $constants = $reflectionClass->getConstants();
            foreach($constants as $fieldName => $fieldId) {
                if (strpos($fieldName, 'FIELD_') === 0) {
                    $field = substr($fieldName, 6);
                    $defaultFieldName = 'DEFAULT_' . $field;
                    if (array_key_exists($defaultFieldName, $constants)) {
                        $this->defaults[$fieldId] = $constants[$defaultFieldName];
                    }
                }
            }
        }

        return $this->defaults;
    }

    /**
     * Sanitize comma separated emails into an array
     *
     * @param string|array $emailList
     */
    protected function parseEmailRecipients(&$emailList)
    {
        if (is_array($emailList)) {
            return;
        }
        if (!$emailList) {
            $emailList = [];
            return;
        }

        $emailList = preg_replace('/\s+/', ' ', $emailList);
        $emailList = array_filter(explode(',', $emailList));
    }
}

