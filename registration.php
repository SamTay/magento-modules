<?php
/**
 * @package     BlueAcorn\AmqpIntegrations
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */

\Magento\Framework\Component\ComponentRegistrar::register(
    \Magento\Framework\Component\ComponentRegistrar::MODULE,
    'BlueAcorn_AmqpBase',
    __DIR__ . DIRECTORY_SEPARATOR . 'Base'
);

\Magento\Framework\Component\ComponentRegistrar::register(
    \Magento\Framework\Component\ComponentRegistrar::MODULE,
    'BlueAcorn_AmqpShipping',
    __DIR__ . DIRECTORY_SEPARATOR . 'Shipping'
);

\Magento\Framework\Component\ComponentRegistrar::register(
    \Magento\Framework\Component\ComponentRegistrar::MODULE,
    'BlueAcorn_AmqpProduct',
    __DIR__ . DIRECTORY_SEPARATOR . 'Product'
);

\Magento\Framework\Component\ComponentRegistrar::register(
    \Magento\Framework\Component\ComponentRegistrar::LIBRARY,
    'blueacorn/framework-entity-map',
    __DIR__ . DIRECTORY_SEPARATOR . 'EntityMap'
);
