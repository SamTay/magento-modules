# ba-attribute-flags
Internal module that associates flags to products based on external product attributes

This module should be used for the scenario when flag values are driven by logic,
typically logic depending on other attribute values. Because of the specificity of this logic,
flags have limited customization. System configuration will allow the client to change
frontend options such as CSS and text, but they cannot create new flags. If a client requires the
ability to create new flags on the fly, refer to the [ba-product-flags](https://github.com/BlueAcornInc/ba-product-flags)
module to see if that fits the requirements.

### Documentation

While the module has limited client side customization, it is very extensible in development. This module could even be used as a base, with separate modules adding flags if that separation is desirable.

##### Adding a flag

To add a flag you need two things:

- Define the flag in the ``config.xml``
```xml
<global>
  ...
  <ba_attributeflags>
    <new>
      <label>New</label>
      <model>ba_attributeflag/flag_new</model>
      <disabled>true</disabled>
    </new>
    <random>
      <label>Wildcard</label>
      <model>ba_attributeflag/flag_random</model>
    </random>
  </ba_attributeflags>
</global>
```
- Add a flag class that implements ``BlueAcorn_AttributeFlag_Model_FlagInterface``
```php
class BlueAcorn_AttributeFlag_Model_Flag_Random
    implements BlueAcorn_AttributeFlag_Model_FlagInterface
{
    /**
     * Check whether $this flag applies to $product argument
     *
     * @param Mage_Catalog_Model_Product $product
     * @return bool
     */
    public function validate(Mage_Catalog_Model_Product $product)
    {
        return rand(0,1) > .8;
    }

    /**
     * Get flag description
     *
     * @return string
     */
    public function getDescription()
    {
        return 'The wildcard flag. Who knows who will get picked next!?';
    }
}
```

##### Tips

As long as those two things are done for each flag, the flag will show up in system configuration as a flag that can be enabled. Note that the interface must be implemented - otherwise the flag will never show up in system configuration and thus never be used on the frontend. See the [source model](https://github.com/BlueAcornInc/ba-attribute-flags/blob/master/app/code/local/BlueAcorn/AttributeFlag/Model/System/Config/Source/Flag.php) for details.

As explained above, this can be a base module and the mere existence of flags differing across clients should **never** require modification to this module. If a client doesn't want a "New" flag then simply add the ``<disabled>`` flag through your ``config.xml``.

There is also a very useful abstract class [FlagAbstract](https://github.com/BlueAcornInc/ba-attribute-flags/blob/master/app/code/local/BlueAcorn/AttributeFlag/Model/FlagAbstract.php) that can provide a great shortcut if the validation is simple. That class can be extended if all that is necessary is a check against an attribute value being "truthy", or if there are two date attributes that need to be validated, such as for [Sale](https://github.com/BlueAcornInc/ba-attribute-flags/blob/master/app/code/local/BlueAcorn/AttributeFlag/Model/Flag/Sale.php) and [New](https://github.com/BlueAcornInc/ba-attribute-flags/blob/master/app/code/local/BlueAcorn/AttributeFlag/Model/Flag/New.php) flags.
