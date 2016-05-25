<?php
/**
 * @package     BlueAcorn\EntityMap
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\EntityMap\Mapper\OptionTable;

use BlueAcorn\EntityMap\Mapper\LabelToOption;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Eav\Model\Entity\Attribute\Source\Table as SourceTable;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Store;

/**
 * Class Dynamic
 *
 * Maps labels "Blue,Bluish Greenish,Green"
 * to option IDs "21,26,22"
 * And will dynamically create [option/value] [Blueish Greenish/26] if doesn't exist
 *
 * Note: External ERPs likely do not have the same concept of multiple stores like Magento does. Therefore
 * this option creator assumes the same store labels are used across all stores.
 *
 * Note: It would be better to use interface abstraction such as AttributeRepository, AttributeInterface::setOptions, and
 * AttributeOptionInterface[], instead of EavConfig, AbstractAttribute->setOption(), etc., but the implementation
 * at the ResourceModel level does not respect the OPTIONS = 'options' constant, and instead looks for implementation
 * level 'option' key.
 */
class Dynamic extends Strict
{
    const SOURCE_MODEL_TABLE = 'Magento\Eav\Model\Entity\Attribute\Source\Table';

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * Strict constructor.
     *
     * @param EavConfig $eavConfig
     * @param LabelToOption $mapper
     * @param StoreManagerInterface $storeManager
     * @param ObjectManagerInterface $objectManager
     * @param string $entityType
     */
    public function __construct(
        EavConfig $eavConfig,
        LabelToOption $mapper,
        StoreManagerInterface $storeManager,
        ObjectManagerInterface $objectManager,
        $entityType
    ) {
        $this->storeManager = $storeManager;
        $this->objectManager = $objectManager;
        parent::__construct(
            $eavConfig,
            $mapper,
            $entityType
        );
    }

    /**
     * Maps labels "Blue,Bluish Greenish,Green"
     * to option IDs "21,26,22"
     * And will dynamically create [option/value] [Blueish Greenish/26] if doesn't exist
     *
     * {@inheritdoc}
     */
    public function map($key, $value)
    {
        $attribute = $this->eavConfig->getAttribute($this->entityType, $key);
        $this->updateOptionTable($attribute, $value);
        return parent::map($key, $value);
    }

    /**
     * Update attribute with any values found in comma separated $value argument
     *
     * @param AbstractAttribute $attribute
     * @param $value
     */
    private function updateOptionTable(AbstractAttribute $attribute, $value)
    {
        $this->checkValidSource();
        $existingOptions = $attribute->getOption();

        // Find new values
        $valuesToAdd = $this->extractNewValues($existingOptions, $value);

        // Get additional option array for merging
        $existingOptionsCount = count($existingOptions['value']);
        $maxSort = max($existingOptions['order']) + 1;
        $newOptions = $this->formatNewOptions($valuesToAdd, $existingOptionsCount, $maxSort);

        $attribute->setOption(
            array_merge_recursive($existingOptions, $newOptions)
        )->save();

        // Overwrite $attribute reference to newly loaded model, because lingering data properties such as
        // _source, _frontend, etc. will remain invalid
        // TODO Needs testing to ensure creation method is correct
        $attribute = $this->objectManager->create($attribute->getAttributeModel())
            ->load($attribute->getId());
    }

    /**
     * Checks that source model uses type SourceTable
     *
     * @param AbstractAttribute $attribute
     * @throws \InvalidArgumentException
     */
    private function checkValidSource(AbstractAttribute $attribute)
    {
        $source = $attribute->getSource();
        if (!$source instanceof SourceTable) {
            throw new \InvalidArgumentException(__(
                'Dynamic options mapper cannot be used for source models other than "%class"',
                ['class' => self::SOURCE_MODEL_TABLE]
            ));
        }
    }

    /**
     * Accepts existing option array (magento format ['delete' => [], 'value' => [], etc])
     * and comma separated string of new values.
     * Returns array of values that dont exist yet
     *
     * @param array $existingOptions
     * @param string $inputValues
     * @return array
     */
    private function extractNewValues($existingOptions, $inputValues)
    {
        $values = explode(',', $inputValues);
        return array_filter($values, function($value) use ($existingOptions) {
            foreach($existingOptions['value'] as $existingValue) {
                if ($existingValue[Store::DEFAULT_STORE_ID] == $value) {
                    return false;
                }
            }
            return true;
        });
    }

    /**
     * Get new values in option array format (['delete' => [], 'value' => [], etc)
     *
     * @param $valuesToAdd
     * @param $existingOptionsCount
     * @param $currentMaxSort
     * @return array
     */
    private function formatNewOptions($valuesToAdd, $existingOptionsCount, $currentMaxSort)
    {
        $newOptions = ['delete' => [], 'order' => [], 'value' => []];
        $i = $existingOptionsCount;
        $sort = $currentMaxSort;
        $storeIds = array_map(function($store) {
            return $store->getId();
        }, $this->storeManager->getStores(true));
        foreach($valuesToAdd as $value) {
            // In reality this key shouldn't matter, but might as well keep consistent with admin
            $key = 'option_' . $i;
            $newOptions['delete'][$key] = '';
            $newOptions['order'][$key] = $sort;
            $newOptions['value'][$key] = array_fill_keys($storeIds, $value);
            $i++;
            $sort++;
        }

        return $newOptions;
    }
}
