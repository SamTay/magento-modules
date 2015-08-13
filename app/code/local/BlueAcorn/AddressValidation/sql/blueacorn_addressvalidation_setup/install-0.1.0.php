<?php
$installer = Mage::getResourceModel('customer/setup', 'core_setup');
$installer->startSetup();

$installer->addAttribute('customer_address', 'verified', array(
    'label' => 'Verified',
    'type' => 'int',
    'input' => 'select',
    'required' => false,
    'visible' => true,
    'adminhtml_only' => true,
    'user_defined' => true,
    'system' => false,
    'source' => 'eav/entity_attribute_source_boolean'
));

$attribute = Mage::getSingleton('eav/config')->getAttribute('customer_address', 'verified');
$attribute->setData('used_in_forms',  array('adminhtml_customer_address'))
    ->save();

$installer->endSetup();