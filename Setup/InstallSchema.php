<?php
/**
 * @package     BlueAcorn\ContentPublisher
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\ContentPublisher\Setup;

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

        $columns = [
            'publish_start' => [
                'type' => DdlTable::TYPE_DATETIME,
                'comment' => 'Publish Start Date',
            ],
            'publish_end' => [
                'type' => DdlTable::TYPE_DATETIME,
                'comment' => 'Publish End Date',
            ]
        ];

        $connection = $setup->getConnection();
        foreach($columns as $name => $definition) {
            $connection->addColumn($setup->getTable('cms_page'), $name, $definition);
        }

        $setup->endSetup();
    }
}
