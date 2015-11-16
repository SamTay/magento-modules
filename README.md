BlueAcorn Ajax Mini Cart
========================

This module adds ajax functionality to the product and category pages so that adding a product to the cart is done via
ajax and the minicart is shown. The current version is implemented against 1.14.2.0, but should drop in to any
1.14+ version of EE. See other branches for earlier versions.

Currently there is a dependency on [green pistachio](https://github.com/BlueAcornInc/green-pistachio) since most of our
sites utitlize that module and it moves the native *minicart.js* to the footer. The dependency is declared to ensure that
*ajaxcart.js* gets included after *minicart.js* as it builds on that functionality.

Version
----
1.0.0

Changelog
----
- **1.0.0**: Complete overhaul of approach. There is now a lot less code included, and the php and javascript
stay as close to native Magento as possible. Approach is now largely based on existing native ajax minicart functionality.
The fancybox has also been removed to keep this base module as simple as possible. This motivation stems from the fact
that this has always been a very buggy internal module.

Sample Sites
----
- [BEK](staging.abeka.com)

Authors
----
- (EE 1.14.2) Sam Tay <sam.tay@blueacorn.com>
- (< EE 1.14.2) Jim Simon <jim@blueacorn.com>, Sam Tay <sam.tay@blueacorn.com>
- (< EE 1.14.0) Thomas Slade <thomas@blueacorn.com>
