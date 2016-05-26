# Product Module
The product module builds on the Base module to set up an integration point for product imports.

### Description

The product module defines create, update, and delete queues along with respective batching queues. By default,
the normal queues run on daemons and the batching queues run by cron. See the `etc/*.xml` files for details.

### Usage

To extend functionality, note that the `etc/queue.xml` consumers are configured to run interface methods when processing
a message. As long as you adhere to the interface, you can leverage the framework `di.xml` preferences to swap out
the Import models, if this base module implementation doesn't fit client needs.

The import interface will only accept `Magento\Catalog\Api\Data\ProductInterface[]`. To ensure that messages are converted
to this format properly, leverage the EntityMap library. (See the `docs/examples/ProductIntegration` directory for
an example). The entity mapping will happen automatically for any message schema that match entity schema defined in
`etc/entity_decode.xml`. There are plenty of before/after events surrounding te entity decoding process as well - poke
around in the `EntityMap/Decoder` class to see what customizations are possible.

The idea for these modules are to be extensible. If you find yourself having to rewrite any of these classes, submit
a Github issue with a description of the use case and problem.
