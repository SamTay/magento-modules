<?php
/**
 * @package     BlueAcorn\ContentPublisher
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\ContentPublisher\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\DataObject;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class Publisher extends AbstractHelper
{
    const STATUS_DISABLE = 0;
    const STATUS_PUBLISH = 1;
    const STATUS_IGNORE = 2;

    const DATA_KEY_START = 'publish_start';
    const DATA_KEY_END = 'publish_end';

    /**
     * @var TimezoneInterface
     */
    protected $_localeDate;

    /**
     * Publisher constructor.
     * @param TimezoneInterface $localeDate
     * @param Context $context
     */
    public function __construct(
        TimezoneInterface $localeDate,
        Context $context
    ) {
        parent::__construct($context);
        $this->_localeDate = $localeDate;
    }

    /**
     * Perhaps remove DataObject type hint
     * @param DataObject $dataObject
     * @return int
     */
    public function getStatus(DataObject $dataObject)
    {
        $start = $dataObject->getPublishStart();
        $end = $dataObject->getPublishEnd();
        // If no dates are set, use current status value
        if (!$start && !$end) {
            return self::STATUS_IGNORE;
        }
        // Otherwise check if within interval
        return $this->isScopeDateTimeInInterval($start, $end)
            ? self::STATUS_PUBLISH
            : self::STATUS_DISABLE;
    }

    /**
     * Adapted from TimeZone::isScopeDateInInterval,
     * which only deals with dates and auto-adds a day buffer to end dates
     *
     * @param $from
     * @param $to
     * @return bool
     */
    public function isScopeDateTimeInInterval($from, $to)
    {
        $scopeTimestamp = $this->_localeDate->scopeTimeStamp();
        $fromTimestamp = strtotime($from);
        $toTimestamp = strtotime($to);

        if ((!$from || $fromTimestamp <= $scopeTimestamp)
            && (!$to || $scopeTimestamp <= $toTimestamp)
        ) {
            return true;
        }

        return false;
    }
}
