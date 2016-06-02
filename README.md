# Blue Acorn Core Module
A core module designed for base functionality on all sites

### Installation
```
composer config repositories.blueacorn/core git git@github.com:blueacorninc/ba-module-core-m2.git
composer require blueacorn/module-core:dev-master
bin/magento setup:upgrade && bin/magento cache:flush
```

**or**

```
mkdir -p app/code/BlueAcorn/Core
git clone git@github.com:blueacorninc/ba-module-core-m2.git app/code/BlueAcorn/Core
bin/magento module:enable BlueAcorn_Core
bin/magento setup:upgrade && bin/magento cache:flush
```

### Version 1.1.0
- Current version 1.1.0 is the first pass at a core module

### Features
- To be determined

### Known Issues
- None yet

### Development Progress
Look in the issues for new features to build or bugs to squash.

