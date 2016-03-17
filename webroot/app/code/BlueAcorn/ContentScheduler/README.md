# Content Scheduler
Blue Acorn module for scheduling alternate CMS blocks and pages

### Installation
```
composer config repositories.blueacorninc/ba-content-scheduler-m2 vcs git@github.com:blueacorninc/ba-content-scheduler-m2.git
composer require blueacorn/content-scheduler:dev-master
bin/magento setup:upgrade && bin/magento cache:flush
```

**or**

```
git clone git@github.com:blueacorninc/ba-content-scheduler-m2.git app/code/BlueAcorn/ContentScheduler
bin/magento module:enable BlueAcorn_ContentScheduler
bin/magento setup:upgrade && bin/magento cache:flush
```

### Version 1.0.0
- Current version 1.0.0 is the first pass at this module on M2

### Configuration per CMS entity
- **Alternate**: Dropdown of other CMS entities
- **Alternate Start**: Date and time to start using alternate content
- **Alternate End**: Date and time to stop using alternate content

### Known Issues
- Magento's current implementation of CMS data and repository interfaces is broken, so the current
methodology is still using models/collections instead of interface abstraction.
- Datepickers are missing form validation messages.
- GMT offset is missing from datetime implementations: the admin user setting the start/end dates needs to be using
a computer with the same timezone as set in system configuration.

### Development Progress
Look in the issues for new features to build or bugs to squash.

