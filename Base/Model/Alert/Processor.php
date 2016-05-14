<?php
/**
 * @package     BlueAcorn\AmqpBase
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\AmqpBase\Model\Alert;

use BlueAcorn\AmqpBase\Api\Data\AlertInterface;
use BlueAcorn\AmqpBase\Helper\Logger;
use BlueAcorn\AmqpBase\Helper\Consumer\Config as ConsumerConfig;
use Magento\Framework\App\State as AppState;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Phrase;

class Processor
{
    const EMAIL_TEMPLATE_ID = 'amqp_alert';

    /**
     * @var TransportBuilder
     */
    protected $transportBuilder;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var ConsumerConfig
     */
    protected $consumerConfig;

    /**
     * @var AppState
     */
    protected $appState;

    /**
     * Processor constructor.
     * @param TransportBuilder $transportBuilder
     * @param ConsumerConfig $consumerConfig
     * @param Logger $logger
     * @param AppState $appState
     */
    public function __construct(
        TransportBuilder $transportBuilder,
        ConsumerConfig $consumerConfig,
        Logger $logger,
        AppState $appState
    ) {
        $this->transportBuilder = $transportBuilder;
        $this->logger = $logger;
        $this->consumerConfig = $consumerConfig;
        $this->appState = $appState;
    }

    public function processAlert(AlertInterface $alert)
    {
        try {
            $recipients = $this->getEmailRecipients($alert);
            $vars = $alert->getData();
            $this->sendEmail($recipients, $vars);
        } catch (\Exception $e) {
            // Squelch all errors in alert queue processing, we don't want to create infinite message loop

            // TODO: Make logger composed of virtual type handlers for different log files
            // magic __call will get logger for next method call
            $this->logger->error($e);
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
}
