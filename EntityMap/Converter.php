<?php
/**
 * @package     BlueAcorn\EntityMap
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\EntityMap;

class Converter
{
    /**
     * @var Decoder
     */
    protected $decoder;

    /**
     * TODO Still need encoder
     * @var Encoder
     */
    protected $encoder;

    /**
     * Converter constructor.
     * @param Decoder $decoder
     * @param Encoder $encoder
     */
    public function __construct(
        Decoder $decoder,
        Encoder $encoder
    ) {
        $this->decoder = $decoder;
        $this->encoder = $encoder;
    }

    /**
     * @param array $data
     * @param $entityType
     * @return array
     */
    public function encode(array $data, $entityType)
    {
        return $this->encoder->encode($data, $entityType);
    }

    /**
     * @param array $data
     * @param $entityType
     * @return array
     */
    public function decode(array $data, $entityType)
    {
        return $this->decoder->decode($data, $entityType);
    }

}
