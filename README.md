# Blue Acorn Core Module
A core module designed for base functionality on all sites

### Installation
```
composer config repositories.blueacorn/core git git@github.com:blueacorninc/m2-module-core.git
composer require blueacorn/module-core:dev-master
bin/magento setup:upgrade && bin/magento cache:flush
```

**or**

```
mkdir -p app/code/BlueAcorn/Core
git clone git@github.com:blueacorninc/m2-module-core.git app/code/BlueAcorn/Core
bin/magento module:enable BlueAcorn_Core
bin/magento setup:upgrade && bin/magento cache:flush
```

### Version 1.1.0
- 1.1.0: adds a product attribute install script
- 1.0.0: the first pass at a core module, adding some convenient helper/logging functionality

### Features
- Allows external modules to quickly create custom loggers to arbitrary file names. See [di.xml](https://github.com/BlueAcornInc/m2-module-core/blob/a2a117b25c5458c74324cb33c90a9bd9e5947c25/etc/di.xml#L32-L62) for more information.
- Publicly expose ModuleManager and ScopeConfig for use in template files (see [Helper](https://github.com/BlueAcornInc/m2-module-core/tree/a2a117b25c5458c74324cb33c90a9bd9e5947c25/Helper) directory
- Product attribute install script generator. Run `./bin/magento dev:attribute-script:product --help` for information

### Known Issues
- None yet

### Development Progress
Look in the issues for new features to build or bugs to squash.

