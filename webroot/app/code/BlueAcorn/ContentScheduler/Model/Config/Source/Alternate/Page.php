<?php
/**
 * @package     BlueAcorn\ContentScheduler
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\ContentScheduler\Model\Config\Source\Alternate;

use BlueAcorn\ContentScheduler\Model\Config\Source\Alternate;
use Magento\Cms\Model\ResourceModel\Page\CollectionFactory as PageCollectionFactory;

class Page extends Alternate
{
    /**
     * Construct
     *
     * @param PageCollectionFactory $pageCollectionFactory
     */
    public function __construct(PageCollectionFactory $pageCollectionFactory)
    {
        $this->_collectionFactory = $pageCollectionFactory;
    }
}
