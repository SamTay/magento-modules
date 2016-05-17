# Content Publisher
Blue Acorn module for scheduling status updates to products and cms pages

### Installation
```
composer config repositories.blueacorn/module-content-publisher git git@github.com:blueacorninc/m2-content-publisher.git
composer require blueacorn/module-content-publisher:dev-master
bin/magento setup:upgrade && bin/magento cache:flush
```

**or**

```
mkdir -p app/code/BlueAcorn/ContentPublisher
git clone git@github.com:blueacorninc/m2-content-publisher.git app/code/BlueAcorn/ContentPublisher
bin/magento module:enable BlueAcorn_ContentPublisher
bin/magento setup:upgrade && bin/magento cache:flush
```

### Version 1.0.0
- Current version 1.0.0 is the first pass at this module on M2

### Configuration per entity
- **Publish Start**: Date and time to start status => enabled
- **Publish End**: Date and time to start status => disabled

### Known Issues
- Magento's current implementation of CMS data and repository interfaces is broken, so the current
methodology is still using models/collections instead of interface abstraction.
- Datepickers are missing form validation messages.
- GMT offset is missing from datetime implementations: the admin user setting the start/end dates needs to be using
a computer with the same timezone as set in system configuration.

### Development Progress
Look in the issues for new features to build or bugs to squash.

