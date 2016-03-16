<?php
/**
 * @package     BlueAcorn\ContentScheduler
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\ContentScheduler\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Cms\Model\ResourceModel\Page\Collection as PageCollection;
use Magento\Cms\Model\ResourceModel\Page\CollectionFactory as PageCollectionFactory;

/** TODO: When Magento fixes CMS data implementations, revert commit to use interfaces/repositories/etc. */
class CmsPage implements OptionSourceInterface
{
    /**
     * Page collection factory
     *
     * @var PageCollectionFactory
     */
    protected $_pageCollectionFactory;

    /**
     * Construct
     *
     * @param PageCollectionFactory $pageCollectionFactory
     */
    public function __construct(PageCollectionFactory $pageCollectionFactory)
    {
        $this->_pageCollectionFactory = $pageCollectionFactory;
    }

    /**
     * Return array of ['value'=>$value, 'label'=>$label] pairs
     * Option to exclude certain page (for `alternate` field)
     *
     * @param bool $withEmpty
     * @param null|string|int $excludeId
     * @return array
     */
    public function toOptionArray($withEmpty = true, $excludeId = null)
    {
        /** @var PageCollection $collection */
        $collection = $this->_pageCollectionFactory->create();
        if ($excludeId) {
            $collection->addFieldToFilter('page_id', ['neq' => $excludeId]);
        }
        $options = [];
        foreach($collection as $page) {
            $options[] = ['value' => $page->getId(), 'label' => $page->getTitle()];
        }
        if ($withEmpty) {
            array_unshift($options, ['value' => '', 'label' => '-- Please Select --']);
        }

        return $options;
    }
}
