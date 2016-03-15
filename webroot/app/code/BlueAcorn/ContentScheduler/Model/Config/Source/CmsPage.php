<?php
/**
 * @package     BlueAcorn\ContentScheduler
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\ContentScheduler\Model\Config\Source;

use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Data\OptionSourceInterface;

class CmsPage implements OptionSourceInterface
{
    /**
     * @var PageRepositoryInterface
     */
    protected $_pageRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $_searchCriteriaBuilder;

    /**
     * Construct
     *
     * @param PageRepositoryInterface $pageRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        PageRepositoryInterface $pageRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    )
    {
        $this->_pageRepository = $pageRepository;
        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * @param bool $withEmpty
     * @param null|string|int $excludeId
     * @return array
     */
    public function toOptionArray($withEmpty = true, $excludeId = null)
    {
        if ($excludeId) {
            $this->_searchCriteriaBuilder->addFilter('page_id', $excludeId, 'neq');
        }
        $searchCriteria = $this->_searchCriteriaBuilder->create(); // Create resets data on the builder
        $results = $this->_pageRepository->getList($searchCriteria);

        $options = [];
        foreach($results->getItems() as $page) {
            // CURRENT BUG IN MAGENTO: $page should be a PageInterface but instead is just an array
            $options[] = ['value' => $page->getId(), 'label' => $page->getTitle()];
        }
        if ($withEmpty) {
            array_unshift($options, ['value' => '', 'label' => '-- Please Select --']);
        }

        return $options;
    }
}
