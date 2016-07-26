# Improved Layered Navigation
Blue Acorn module for improving the native layered navigation

### Installation
```
composer config repositories.blueacorn/layered-navigation git git@github.com:blueacorninc/m2-layered-navigation.git
composer require blueacorn/layered-navigation:dev-master
bin/magento setup:upgrade && bin/magento cache:flush
```

**or**

```
mkdir -p app/code/BlueAcorn/LayeredNavigation/navigation
git clone git@github.com:blueacorninc/m2-layered-navigation.git app/code/BlueAcorn/LayeredNavigation
bin/magento module:enable BlueAcorn_LayeredNavigation
bin/magento setup:upgrade && bin/magento cache:flush
```

### Version 0.0.0
- Current version 0.0.0 is still under initial development

### Features

##### Multi Value Filtering
Allows filtering by multiple values per attribute.

##### Nested Filtering
Allow dependencies between attribute filters. For example, the

    {"Blue Shades": "Aqua, Sky Blue, Navy, Royal Blue, Cobalt"}

filter only shows up if

    {"Color": "Blue"}

that is, if the Color => Blue filter is selected, or if all items in the collection have "Color" attribute value equal to
"Blue". There is configuration to restrict showing the dependent filters if the attribute is a multiselect and more than
one value is selected (or if some items in the collection have more than the dependent value set).

### Known Issues
None yet.

### Development Progress
Look in the issues for new features to build or bugs to squash.

