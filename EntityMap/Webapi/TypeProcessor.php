<?php
/**
 * @package     BlueAcorn\EntityMap
 * @version     0.2.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\EntityMap\Webapi;

/**
 * Rewrite framework TypeProcessor to allow callable types
 * This is an obtrusive override -- leaving global di preference up to implementation
 */
class TypeProcessor extends \Magento\Framework\Reflection\TypeProcessor
{
    /**
     * Pre-normalized type constants
     */
    const CALLABLE_TYPE = 'callable';
    const CLOSURE_TYPE = '\Closure';

    /**
     * Override to include callables & closures as simple types
     *
     * {@inheritdoc}
     */
    public function isTypeSimple($type)
    {
        $type = $this->normalizeType($type);
        if ($this->isArrayType($type)) {
            $type = $this->getArrayItemType($type);
        }

        return parent::isTypeSimple($type) || in_array($type, [
            self::CALLABLE_TYPE,
            self::CLOSURE_TYPE
        ]);
    }

    /**
     * Override to stop setting type on callables & closures
     *
     * {@inheritdoc}
     */
    protected function setType(&$value, $type)
    {
        switch($type) {
            case 'bool':
            case 'boolean':
                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                return true;
            case self::CALLABLE_TYPE:
                if (!is_callable($value)) {
                    return false;
                }
                return true;
            case self::CLOSURE_TYPE:
                if (!$value instanceof \Closure) {
                    return false;
                }
                return true;
            default:
                return settype($value, $type);
        }
    }

    /**
     * Override to improve array processing
     * - No longer locks in arrays of the same type, can specify mixed[]
     * - Fixed ghost bug of not checking array element value null
     *
     * {@inheritdoc}
     */
    public function processSimpleAndAnyType($value, $type)
    {
        $isArrayType = $this->isArrayType($type);
        if ($isArrayType && is_array($value)) {
            $arrayItemType = $this->getArrayItemType($type);
            foreach (array_keys($value) as $key) {
                $value[$key] = parent::processSimpleAndAnyType($value[$key], $arrayItemType);
            }
            return $value;
        }

        return parent::processSimpleAndAnyType($value, $type);
    }
}
