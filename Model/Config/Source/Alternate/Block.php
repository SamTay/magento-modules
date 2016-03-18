<?php
/**
 * @package     BlueAcorn\ContentScheduler
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\ContentScheduler\Model\Config\Source\Alternate;

use BlueAcorn\ContentScheduler\Model\Config\Source\Alternate;
use Magento\Cms\Model\ResourceModel\Block\CollectionFactory as BlockCollectionFactory;

/**
 * Class Block
 * Source model for alternate block entities
 * Note we can remove these block/page classes in favor of virtual types if desired
 */
class Block extends Alternate
{
    /**
     * Construct
     *
     * @param BlockCollectionFactory $blockCollectionFactory
     */
    public function __construct(BlockCollectionFactory $blockCollectionFactory)
    {
        $this->_collectionFactory = $blockCollectionFactory;
    }
}
