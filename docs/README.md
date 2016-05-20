# Documentation
Refer to specific submodule documentation for details on the amqp-integrations framework that this congolomerate provides.
This `docs` directory shows how these modules can be used in a particular site implementation. See the `docs/examples` subdirectory
for example modules that will live in client repositories.

## Examples
#### Product Integration
See this module for an example of leveraging this framework for specific PIM imports. Note the `entity_decode.xml` file,
which automatically handles three important sequential steps:

###### 1. Simple key maps
  - xml: `<key_map from="externalKey" to="internal_key" />`
  - result: `['externalKey' => $value] -> ['internal_key' => $value]`
  
###### 2. Aggregating attributes
  - xml: `<aggregate id="aggregate"><key id="first_piece" /><key id="second_piece" /></aggregate>`
  - result: `['first_piece' => '1', 'second_piece => '2'] -> ['aggregate' => ['first_piece' => '1', 'second_piece => '2']]`
  - note: This will mainly be useful for attribute mapping after aggregation
  
###### 3. Attribute key,value maps
  - xml: `<attribute_map code="color" mapper="BlueAcorn\EntityMap\Mapper\OptionTable />`
  - result: `['color' => 'blue,green'] -> ['color' => '82,24']`
  
###### 4. Default mapper specification
  - xml: `<default_mapper class="BlueAcorn\EntityMap\Mapper\OneToOne" />`
  - result: maps anything not specified via this mapper class

## Support
If the documentation here is inadequate, please submit a github issue.
