<?php
/**
 * @package     BlueAcorn\LayeredNavigation
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\LayeredNavigation\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table as DdlTable;

class InstallSchema implements InstallSchemaInterface
{
    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        $table = $setup->getConnection()
            ->newTable('ba_layerednav_filter_dependency')
            ->addColumn(
                'dependency_id',
                DdlTable::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Dependency ID'
            )->addColumn(
                'attribute_id',
                DdlTable::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                'Attribute ID'
            )->addColumn(
                'depends_attribute_id',
                DdlTable::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                'Depends Attribute ID'
            )->addColumn(
                'depends_value',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => false, 'default' => null],
                'Depends Value'
            )->addColumn(
                'store_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                'Store ID'
            )->addIndex(
                $setup->getIdxName('ba_layerednav_filter_dependency', ['attribute_id']),
                ['attribute_id']
            )->addIndex(
                $setup->getIdxName('ba_layerednav_filter_dependency', ['depends_attribute_id']),
                ['depends_attribute_id']
            )->addIndex(
                $setup->getIdxName('ba_layerednav_filter_dependency', ['store_id']),
                ['store_id']
            )->addForeignKey(
                $setup->getFkName('ba_layerednav_filter_dependency', 'attribute_id', 'eav_attribute', 'attribute_id'),
                'attribute_id',
                $setup->getTable('eav_attribute'),
                'attribute_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )->addForeignKey(
                $setup->getFkName('ba_layerednav_filter_dependency', 'depends_attribute_id', 'eav_attribute', 'attribute_id'),
                'depends_attribute_id',
                $setup->getTable('eav_attribute'),
                'attribute_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )->addForeignKey(
                $setup->getFkName('ba_layerednav_filter_dependency', 'store_id', 'store', 'store_id'),
                'store_id',
                $setup->getTable('store'),
                'store_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )->setComment(
                'Layered Navigation Filter Dependency Table'
            );
        $setup->getConnection()->createTable($table);
        $setup->endSetup();
    }
}
