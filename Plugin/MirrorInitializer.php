<?php
/**
 * @package     BlueAcorn\LayeredNavigation
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\LayeredNavigation\Plugin;

use Magento\Catalog\Model\Category;
use Magento\CatalogSearch\Model\Layer\Category\ItemCollectionProvider;
use BlueAcorn\LayeredNavigation\Model\Layer\CollectionMirror;

class MirrorInitializer
{
    /** @var CollectionMirror */
    private $collectionMirror;

    /**
     * MirrorInitializer constructor.
     * @param CollectionMirror $collectionMirror
     */
    public function __construct(CollectionMirror $collectionMirror)
    {
        $this->collectionMirror = $collectionMirror;
    }

    /**
     * Applies initial category filter to collection mirror
     *
     * @param Category $category
     */
    public function beforeGetCollection(ItemCollectionProvider $subject, Category $category)
    {
        // TODO magento doesn't apply filter in search context (in this case $category is root category)
        $this->collectionMirror->addCategoryFilter($category);
    }
}
