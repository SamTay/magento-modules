<?php
/**
 * @package     BlueAcorn\AmqpBase
 * @version     0.2.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\AmqpBase\Model\Alert;

use BlueAcorn\AmqpBase\Api\Data\AlertInterface;
use BlueAcorn\AmqpBase\Helper\LogManager;
use BlueAcorn\AmqpBase\Helper\Consumer\Config as ConsumerConfig;
use Magento\Framework\App\State as AppState;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Phrase;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class Processor
{
    const EMAIL_TEMPLATE_ID = 'amqp_alert';

    /**
     * @var TransportBuilder
     */
    protected $transportBuilder;

    /**
     * @var LogManager
     */
    protected $logManager;

    /**
     * @var ConsumerConfig
     */
    protected $consumerConfig;

    /**
     * @var AppState
     */
    protected $appState;

    /**
     * @var DataObjectProcessor
     */
    protected $dataObjectProcessor;

    /**
     * @var TimezoneInterface
     */
    protected $timezone;

    /**
     * Processor constructor.
     * @param TransportBuilder $transportBuilder
     * @param ConsumerConfig $consumerConfig
     * @param LogManager $logManager
     * @param AppState $appState
     * @param DataObjectProcessor $dataObjectProcessor
     * @param TimezoneInterface $timezone
     */
    public function __construct(
        TransportBuilder $transportBuilder,
        ConsumerConfig $consumerConfig,
        LogManager $logManager,
        AppState $appState,
        DataObjectProcessor $dataObjectProcessor,
        TimezoneInterface $timezone
    ) {
        $this->transportBuilder = $transportBuilder;
        $this->logManager = $logManager;
        $this->consumerConfig = $consumerConfig;
        $this->appState = $appState;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->timezone = $timezone;
    }

    public function processAlert(AlertInterface $alert)
    {
        try {
            $recipients = $this->getEmailRecipients($alert);
            $vars = $this->dataObjectProcessor->buildOutputDataArray($alert, 'BlueAcorn\AmqpBase\Api\Data\AlertInterface');
            $this->addVars($vars);
            $this->sendEmail($recipients, $vars);
        } catch (\Exception $e) {
            // Squelch all errors in alert queue processing, we don't want to create infinite message loop
            $this->logManager->getLogger()->error($e);
        }
    }

    /**
     * Send alert email
     *
     * @param array $recipients
     * @param array $vars
     */
    protected function sendEmail(array $recipients, array $vars)
    {
        $this->appState->emulateAreaCode(
            \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE,
            function() use ($recipients, $vars) {
                $this->transportBuilder
                    ->addTo($recipients)
                    ->setTemplateVars($vars)
                    ->setTemplateIdentifier(self::EMAIL_TEMPLATE_ID)
                    ->setTemplateOptions([
                        'area' => \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE,
                        'store' => \Magento\Store\Model\Store::DEFAULT_STORE_ID
                    ])
                    ->getTransport()
                    ->sendMessage();
            }
        );
    }

    /**
     * Get recipients from alert or defaults
     *
     * @param AlertInterface $alert
     * @return array
     * @throws LocalizedException
     */
    protected function getEmailRecipients(AlertInterface $alert)
    {
        $consumer = $alert->getConsumer();
        $recipients = $alert->getEmailRecipients() ?: $this->consumerConfig->getEmailRecipients($consumer);
        if (!$recipients) {
            throw new LocalizedException(new Phrase(
                'Alert received with no email recipients, and no default email configuration was found. Data: "%data"',
                ['data' => var_export($alert->getData(), true)]
            ));
        }
        return $recipients;
    }

    /**
     * Add template variables (by reference!)
     *
     * @param $vars
     */
    protected function addVars(&$vars)
    {
        $vars['date_and_time'] = (empty($vars['timestamp']))
            ? ''
            : $this->timezone->formatDateTime($vars['timestamp']);
    }
}
