# Amqp Integrations Conglomerate
Blue Acorn conglomerate of modules for AMQP integrations. 

### Description
The current magento module-amqp is still under development, so this module will likely see volatile change in the near future.
For details on the current version 2.0 versus the next 2.1 update,
see [here](https://community.magento.com/t5/Just-Ask-Alan/RabbitMQ-lt-gt-Magento-2-EE-integration/m-p/25415#M153).

### Installation
```
composer config repositories.blueacorn/amqp-integrations git git@github.com:blueacorninc/m2-amqp-integrations.git
composer require blueacorn/amqp-integrations:dev-master
bin/magento setup:upgrade && bin/magento cache:flush
```
Note, if any of the integrations are unnecessary, simply use ``bin/magento module:disable BlueAcorn_AmqpModule`` before
issuing the ``setup:upgrade`` command.

### Usage

##### RabbitMQ Setup
- Configure the amqp connection in ``app/etc/env.php``. For example:

```php
'queue' => [
    'amqp' => [
        'host' => 'localhost',
        'port' => '5672',
        'user' => 'guest',
        'password' => 'guest',
        'virtualhost' => '/',
        'ssl' => '',
    ]
]
```
- Use the skel to keep production values in ``app/etc/env.php`` and empty or testing values on local or QA instances respectively.
- Next, read the [devdocs](http://devdocs.magento.com/guides/v2.0/config-guide/mq/config-mq.html) for details on defining a topology
through modules' ``etc/queue.xml`` files. In addition, you can find examples in a few core modules such as ``module-scalable-inventory``
or the submodules of this repository.
- Finally, use the added console command ``queue:topology:install`` to set up rabbitMQ per your configuration.

### Version 1.0.0
- Current version 1.0.0 is the first pass at this module on M2

### Configuration
- **TBD**

### Known Issues
- **TBD**

### Development Progress
Look in the issues for new features to build or bugs to squash.

