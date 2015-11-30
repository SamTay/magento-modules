<?php
/**
 * @package     BlueAcorn\AttributeFlag
 * @version     0.1.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2015 Blue Acorn, Inc.
 */
interface BlueAcorn_AttributeFlag_Model_FlagInterface
{
    /**
     * Check whether $this flag applies to $product argument
     *
     * @param Mage_Catalog_Model_Product $product
     * @return bool
     */
    public function validate(Mage_Catalog_Model_Product $product);

    /**
     * Get description that will be used in system configuration
     *
     * @return string
     */
    public function getDescription();
}