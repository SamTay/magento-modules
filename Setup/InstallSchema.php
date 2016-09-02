<?php
/**
 * @package     BlueAcorn\LayeredNavigation
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\LayeredNavigation\Setup;

use Magento\Framework\DB\Adapter\AdapterInterface;
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

        /**
         * Create dependency entity table
         */
        $table = $setup->getConnection()
            ->newTable($setup->getTable('ba_layerednav_filter_dependency'))
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
                'option_id',
                DdlTable::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                'Option ID'
            )->addColumn(
                'status',
                DdlTable::TYPE_SMALLINT,
                null,
                ['nullable' => false, 'default' => '1'],
                'Is Dependency Active'
            )->addColumn(
                'creation_time',
                DdlTable::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => DdlTable::TIMESTAMP_INIT],
                'Dependency Creation Time'
            )->addColumn(
                'update_time',
                DdlTable::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => DdlTable::TIMESTAMP_INIT_UPDATE],
                'Dependency Modification Time'
            )->addIndex(
                $setup->getIdxName('ba_layerednav_filter_dependency', ['attribute_id', 'option_id'], AdapterInterface::INDEX_TYPE_UNIQUE),
                ['attribute_id', 'option_id'],
                ['type' => AdapterInterface::INDEX_TYPE_UNIQUE]
            )->addIndex(
                $setup->getIdxName('ba_layerednav_filter_dependency', ['attribute_id']),
                ['attribute_id']
            )->addForeignKey(
                $setup->getFkName('ba_layerednav_filter_dependency', 'attribute_id', 'eav_attribute', 'attribute_id'),
                'attribute_id',
                $setup->getTable('eav_attribute'),
                'attribute_id',
                DdlTable::ACTION_CASCADE
            )->addForeignKey(
                $setup->getFkName('ba_layerednav_filter_dependency', 'option_id', 'eav_attribute_option', 'option_id'),
                'option_id',
                $setup->getTable('eav_attribute_option'),
                'option_id',
                DdlTable::ACTION_CASCADE
            )->setComment(
                'Layered Navigation Filter Dependency Table'
            );
        $setup->getConnection()->createTable($table);

        /**
         * Create link table for dependency entity store associations
         */
        $table = $setup->getConnection()
            ->newTable($setup->getTable('ba_layerednav_filter_dependency_store'))
            ->addColumn(
                'dependency_id',
                DdlTable::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'primary' => true],
                'Dependency ID'
            )->addColumn(
                'store_id',
                DdlTable::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'primary' => true],
                'Store ID'
            )->addIndex(
                $setup->getIdxName('ba_layerednav_filter_dependency_store', ['store_id']),
                ['store_id']
            )->addForeignKey(
                $setup->getFkName('ba_layerednav_filter_dependency_store', 'dependency_id', 'ba_layerednav_filter_dependency', 'dependency_id'),
                'dependency_id',
                $setup->getTable('ba_layerednav_filter_dependency'),
                'dependency_id',
                DdlTable::ACTION_CASCADE
            )->addForeignKey(
                $setup->getFkName('ba_layerednav_filter_dependency_store', 'store_id', 'store', 'store_id'),
                'store_id',
                $setup->getTable('store'),
                'store_id',
                DdlTable::ACTION_CASCADE
            )->setComment(
                'Filter Dependency To Store Linkage Table'
            );
        $setup->getConnection()->createTable($table);

        $setup->endSetup();
    }
}
