<?php

namespace PureMachine\Bundle\StoreBundle\Adapter;

use PureMachine\Bundle\StoreBundle\Store\Base\BaseStore;

interface StoreAdapterInterface
{
    /**
     * Called before validation started
     */
    public function validate(BaseStore $store);
    public function hydrate(BaseStore $store, $dataSource);

    /**
     * called before property settings
     */
    public function getPropertyValue(BaseStore $store, $property, $index=null);
    public function setPropertyValue(BaseStore $store, $property, $value);

    /**
     * To add personal annotation to storeSchema
     */
    public static function extendStoreSchema($annotation, $property, array $definition);
}
