# ba-attribute-flags
Internal module that associates flags to products based on external product attributes

This module should be used for the scenario when flag values are driven by logic,
typically logic depending on other attribute values. Because of the specificity of this logic,
flags have limited customization. System configuration will allow the client to change
frontend options such as CSS and text, but they cannot create new flags. If a client requires the
ability to create new flags on the fly, refer to the [ba-product-flags](https://github.com/BlueAcornInc/ba-product-flags)
module to see if that fits the requirements.

### Documentation

TODO: Example of adding new flag