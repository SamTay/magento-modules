# Base Module
The base module handles the lower level AMQP configuration and convenient functionality entry points, which allows us
to separate concerns with respect to each entity level integration.

### Description
The module builds off of the magento

1. framework-message-queue
2. module-message-queue
3. module-amqp

Its main conveniences lie in the quick consumer configuration and instantiation. Through XML, system configuration, and
console commands, developers can easily specify new consumers, while administrators can customize the number of daemons
per consumer, and even customize which emails to alert when errors are encountered in message consumption.

### Usage
Direct usage of this module should not be common for site specific implementations. If a site has some custom entity that
is being managed between ERP and Magento, and needs a message queue to facilitate the integration, refer to the entity specific
submodules for details on how to set up a queue and consumer (start with `queue.xml` and `consumer.xml`).