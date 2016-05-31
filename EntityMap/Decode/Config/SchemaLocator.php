<?php
/**
 * @package     BlueAcorn\EntityMap
 * @version     0.2.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\EntityMap\Decode\Config;

use Magento\Framework\Config\Dom\UrnResolver;
use Magento\Framework\Config\SchemaLocatorInterface;

/**
 * Schema locator for EntityMap
 */
class SchemaLocator implements SchemaLocatorInterface
{
    /**
     * Path to corresponding XSD file with validation rules for merged config
     *
     * @var string
     */
    protected $schema;

    /**
     * Path to corresponding XSD file with validation rules for separate config files
     *
     * @var string
     */
    protected $perFileSchema;

    /**
     * Initialize dependencies.
     * @param UrnResolver $urnResolver
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function __construct(
        UrnResolver $urnResolver
    )
    {
        $this->schema = $urnResolver->getRealPath('urn:blueacorn:framework-entity-map:etc/entity_decode.xsd');
        $this->perFileSchema = $this->schema;
    }

    /**
     * Get path to merged config schema
     *
     * @return string
     */
    public function getSchema()
    {
        return $this->schema;
    }

    /**
     * Get path to per file validation schema
     *
     * @return string
     */
    public function getPerFileSchema()
    {
        return $this->perFileSchema;
    }
}
