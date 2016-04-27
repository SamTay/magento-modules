<?php
/**
 * @package     BlueAcorn\ContentPublisher
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */

// AmqpIntegrations/Base can set up cron group

//\Magento\Framework\Component\ComponentRegistrar::register(
//    \Magento\Framework\Component\ComponentRegistrar::MODULE,
//    'BlueAcorn_AmqpIntegrations',
//    __DIR__
//);

\Magento\Framework\Component\ComponentRegistrar::register(
    \Magento\Framework\Component\ComponentRegistrar::MODULE,
    'BlueAcorn_AmqpShipping',
    __DIR__ . DIRECTORY_SEPARATOR . 'Shipping'
);
