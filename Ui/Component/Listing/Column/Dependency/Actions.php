<?php
/**
 * @package     BlueAcorn\LayeredNavigation
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\LayeredNavigation\Ui\Component\Listing\Column\Dependency;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class Actions extends Column
{
    /**
     * Url path
     */
    const URL_PATH_EDIT = 'ba_layerednav/dependency/edit';
    const URL_PATH_DELETE = 'ba_layerednav/dependency/delete';
    const URL_PATH_DETAILS = 'ba_layerednav/dependency/details';

    /** @var UrlInterface */
    protected $urlBuilder;

    /**
     * Constructor
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }
        foreach ($dataSource['data']['items'] as &$item) {
            if (!isset($item['dependency_id'])) {
                continue;
            }
            $item[$this->getData('name')] = [
                'edit' => [
                    'href' => $this->urlBuilder->getUrl(
                        static::URL_PATH_EDIT,
                        [
                            'dependency_id' => $item['dependency_id']
                        ]
                    ),
                    'label' => __('Edit')
                ],
                'details' => [
                    'href' => $this->urlBuilder->getUrl(
                        static::URL_PATH_DETAILS,
                        [
                            'dependency_id' => $item['dependency_id']
                        ]
                    ),
                    'label' => __('Details')
                ],
                'delete' => [
                    'href' => $this->urlBuilder->getUrl(
                        static::URL_PATH_DELETE,
                        [
                            'dependency_id' => $item['dependency_id']
                        ]
                    ),
                    'label' => __('Delete'),
                    'confirm' => [
                        'title' => __('Delete Dependency #${ $.$data.dependency_id }'),
                        'message' => __('Are you sure you wan\'t to delete dependency #${ $.$data.dependency_id }?')
                    ]
                ]
            ];
        }
        return $dataSource;
    }
}
