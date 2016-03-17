<?php
/**
 * @package     BlueAcorn\ContentScheduler
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\ContentScheduler\Setup;

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
            'alternate' => [
                'type' => DdlTable::TYPE_INTEGER,
                'unsigned' => true,
                'comment' => 'Alternate CMS Content'
            ],
            'alternate_start' => [
                'type' => DdlTable::TYPE_DATETIME,
                'comment' => 'Alternate Start Date',
            ],
            'alternate_end' => [
                'type' => DdlTable::TYPE_DATETIME,
                'comment' => 'Alternate End Date',
            ]
        ];

        $connection = $setup->getConnection();
        foreach(['cms_page', 'cms_block'] as $cmsTable) {
            foreach($columns as $name => $definition) {
                $connection->addColumn($setup->getTable($cmsTable), $name, $definition);
            }
        }

        $setup->endSetup();
    }
}
