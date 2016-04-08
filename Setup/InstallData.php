<?php
/**
 * @package     BlueAcorn\ContentPublisher
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\ContentPublisher\Setup;

use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class InstallData implements InstallDataInterface
{
    /**
     * EAV setup factory
     *
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * Init
     *
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(EavSetupFactory $eavSetupFactory)
    {
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);
        $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, 'publish_start');
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'publish_start',
            [
                'label' => 'Enable Start Date',
                'type' => 'datetime',
                'input' => 'date',
                'input_renderer' => 'BlueAcorn\ContentPublisher\Block\Adminhtml\Form\Element\Datetime',
                'class' => 'validate-date validate-date-range date-range-publish-from',
                'backend' => 'BlueAcorn\ContentPublisher\Model\Entity\Attribute\Backend\Startdate',
                'required' => false,
                'group' => 'Product Details',
                'sort_order' => 18
            ]
        );
        $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, 'publish_end');
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'publish_end',
            [
                'label' => 'Enable End Date',
                'type' => 'datetime',
                'input' => 'date',
                'input_renderer' => 'BlueAcorn\ContentPublisher\Block\Adminhtml\Form\Element\Datetime',
                'class' => 'validate-date validate-date-range date-range-publish-to',
                'backend' => 'Magento\Eav\Model\Entity\Attribute\Backend\Datetime',
                'required' => false,
                'group' => 'Product Details',
                'sort_order' => 19,
                'note' => __('If you want to keep a product enabled indefinitely, leave the start and end dates empty.'
                    . ' If an "Enable End Date" exists, the product will remain disabled after the end date has passed.')
            ]
        );
    }
}
