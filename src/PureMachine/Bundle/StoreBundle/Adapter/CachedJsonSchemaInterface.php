<?php

namespace PureMachine\Bundle\StoreBundle\Adapter;

interface CachedJsonSchemaInterface
{
    /**
     * Return the schema for a class
     * Usually used to improve cache system
     *
     * return null the not implemented
     */
    public static function getJsonSchema($className);
    public static function cacheJsonSchema($className, $schema);
}
