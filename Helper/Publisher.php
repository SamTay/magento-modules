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
     * @var Debug
     */
    protected $_debug;

    /**
     * @param TimezoneInterface $localeDate
     * @param Debug $debug
     * @param Context $context
     */
    public function __construct(
        TimezoneInterface $localeDate,
        Debug $debug,
        Context $context
    ) {
        parent::__construct($context);
        $this->_localeDate = $localeDate;
        $this->_debug = $debug;
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

        $this->_debug->log("From: $fromTimestamp, Now: $scopeTimestamp, To: $toTimestamp");
        foreach(['scope', 'from', 'to'] as $var) {
            $date = $this->_localeDate->date(${$var . 'Timestamp'});
            ${$var . 'Formatted'} = $this->_localeDate->formatDateTime($date);
        }
        $this->_debug->log("From: $fromFormatted, Now: $scopeFormatted, To: $toFormatted");

        if ((!$from || $fromTimestamp <= $scopeTimestamp)
            && (!$to || $scopeTimestamp <= $toTimestamp)
        ) {
            $this->_debug->log("Inside publishing interval");
            return true;
        }

        $this->_debug->log("Outside publishing interval");
        return false;
    }
}
