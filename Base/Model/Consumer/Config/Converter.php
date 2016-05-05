<?php
/**
 * @package     BlueAcorn\AmqpBase
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\AmqpBase\Model\Consumer\Config;

use Magento\Framework\Config\ConverterInterface;
use Magento\Framework\MessageQueue\Config\Data as QueueConfig;
use Magento\Framework\MessageQueue\Config\Converter as QueueConfigConverter;

/**
 * Converts consumer parameters from XML files
 */
class Converter implements ConverterInterface
{
    /**
     * Unique identifier of consumer node.
     */
    const NAME_ATTRIBUTE = 'name';

    /**
     * @var QueueConfig
     */
    protected $queueConfig;

    /**
     * Converter constructor.
     * @param QueueConfig $queueConfig
     */
    public function __construct(QueueConfig $queueConfig)
    {
        $this->queueConfig = $queueConfig;
    }

    /**
     * Converting data to array type
     *
     * @param mixed $source
     * @return array
     * @throws \InvalidArgumentException
     */
    public function convert($source)
    {
        $output = [];
        if (!$source instanceof \DOMDocument) {
            return $output;
        }

        $consumers = $source->getElementsByTagName('consumer');
        foreach ($consumers as $consumer) {
            /** @var $consumer \DOMElement */
            // Check for required name attribute
            if (!$consumer->hasAttribute(self::NAME_ATTRIBUTE)) {
                throw new \InvalidArgumentException('Attribute "'. self::NAME_ATTRIBUTE .'" does not exist on consumer element.');
            }

            $consumerName = $consumer->getAttribute(self::NAME_ATTRIBUTE);
            // Check consuer name exists in queue.xml configuration
            if (!$this->doesConsumerExist($consumerName)) {
                throw new \InvalidArgumentException('Consumer ' . $consumerName . ' is not configured in queue.xml');
            }

            // Gather all of the children node data
            foreach ($consumer->childNodes as $child) {
                if (!$child instanceof \DOMElement) {
                    continue;
                }
                $output[$consumer->getAttribute(self::NAME_ATTRIBUTE)][$child->nodeName] = $child->nodeValue;
            }
        }

        return $output;
    }

    /**
     * Check if consumer exists in message-queue-framework configuration (i.e. queue.xml files)
     *
     * @param string $consumerName
     * @return bool
     */
    protected function doesConsumerExist($consumerName)
    {
        $consumerPath = implode('/', [QueueConfigConverter::CONSUMERS, $consumerName]);
        return (bool)$this->queueConfig->get($consumerPath);
    }
}
