<?php
/**
 * @package     BlueAcorn\AmqpBase
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\AmqpBase\Plugin\System\Config\Initial;

use BlueAcorn\AmqpBase\Plugin\System\Config\Structure\Converter as StructureConverter;

/**
 * Class Converter
 * Plugin that sets up default values for each consumer system configuration group
 */
class Converter
{
    // TODO: Remove this in favor of defaults per consumer via consumer.xml file
    const DEFAULT_DAEMON_COUNT = 0;
    const DEFAULT_EMAIL_RECIPIENTS = '';
    const DEFAULT_EMAIL_SUBJECT = '';

    const DAEMON_COUNT_FIELD = 'daemon_count';
    const EMAIL_RECIPIENTS_FIELD = 'email_recipients';
    const EMAIL_SUBJECT_FIELD = 'email_subject';

    /**
     * @var StructureConverter
     */
    protected $structureConverter;

    /**
     * Converter constructor.
     * @param StructureConverter $structureConverter
     */
    public function __construct(StructureConverter $structureConverter)
    {
        $this->structureConverter = $structureConverter;
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
        // TODO: Put these into a consumer.xml file for default value specification
        if (isset($result['data']['default'][StructureConverter::SECTION])) {
            $result['data']['default'][StructureConverter::SECTION][StructureConverter::GROUP] = $this->getDefaultValues();
        }
        return $result;
    }

    /**
     * Return default values specified by class constants
     * TODO: Remove this method and rely on consumer.xml files
     * @return array
     */
    protected function getDefaultValues()
    {
        $consumers = $this->structureConverter->getConsumerList();
        array_walk($consumers, function(&$value) {
            $value = [
                self::DAEMON_COUNT_FIELD => self::DEFAULT_DAEMON_COUNT,
                self::EMAIL_RECIPIENTS_FIELD => self::DEFAULT_EMAIL_RECIPIENTS,
                self::EMAIL_SUBJECT_FIELD => self::DEFAULT_EMAIL_SUBJECT,
            ];
        });
        return $consumers;
    }
}
