<?php
namespace PureMachine\Bundle\SDKBundle\Store\Base;

use PureMachine\Bundle\SDKBundle\Store\Type\PBString;
use PureMachine\Bundle\SDKBundle\Store\Type\Boolean;

/**
 * Class that centralize all method to convert and check data
 * injected inside a Store
 *
 * At long term view, this class will be the starting point to
 * kill JsonSchemaFactory.
 */
class StoreHelper
{
    public static function isInstanceOf($object, array $classArray)
    {
        foreach($classArray as $class)
            if ($object instanceof $class) return true;
        return false;
    }

    /**
     * @param $inValue
     * @param  bool     $includePrivate
     * @param  bool     $includeInternal
     * @return stdClass
     */
    public static function serialize($inValue, $includePrivate=false, $includeInternal=true,
                                     $removeNullValues=false, $dateAsISO8601=false)
    {
        if ($inValue instanceof BaseStore) {
            return $inValue->serialize($includePrivate, $includeInternal, $removeNullValues, $dateAsISO8601);
        }

        if (is_array($inValue)) {
            $value = array();
            foreach ($inValue as $key => $item) {
                $value[$key] = static::serialize($item);
            }

            return $value;
        }

        return $inValue;
    }

    public static function unSerialize($inValue, array $defaultClassNames,
                                       $deprecated=null)
    {
        if ($inValue instanceof BaseStore) {
            return $inValue;
        }

        if ($inValue instanceof \stdClass) {
            //If there is a StoreClass defined, we try to initialize it
            $storeClass = self::getStoreClass($inValue, $defaultClassNames);
            if ($storeClass) {
                $value = self::createClass(
                        $storeClass,
                        $inValue
                        );
            } else {
                $value = $inValue;
            }
        } elseif (is_array($inValue)) {
            //If the property if an array of Store, we create it
            $value = array();
            foreach ($inValue as $key => $item) {

                /**
                 * array of store case.
                 * But if we recieve store as array (array in array),
                 * we convert it to object
                 */
                if (count($defaultClassNames) >0 && is_array($item)) {
                    $item = (object) $item;
                }

                $value[$key] = static::unSerialize($item, $defaultClassNames);
            }
        } else {
            $value = $inValue;
        }

        return $value;
    }

    /**
     * Create a class if exists and not abstract
     */
    public static function createClass(
            $class,
            $data
            )
    {
        if (!class_exists($class)) {
            return null;
        }
        $ref = new \ReflectionClass($class);
        if ($ref->isAbstract()) {
            return null;
        }
        $store =  new $class($data);

        return $store;
    }

    /**
     * return a string that represent a store.
     *
     * Look first inside the $inValue.
     * If mot, take if from the definition
     * and check if class exists
     * in definition
     *
     * @param array $definition
     */
    public static function getStoreClass($inValue, array $defaultClassName)
    {
        //Get the class inside the values
        if ($inValue && isset($inValue->_className) && class_exists($inValue->_className)) {
            return $inValue->_className;
        }

        //We take it from the array if there is only one
        if (count($defaultClassName) == 1 && class_exists($defaultClassName[0])) {
            return $defaultClassName[0];
        }

        //Multiple options, using type to decide the store class
        if (count($defaultClassName) > 1) {
            $matchedClasses = [];
            foreach ($defaultClassName as $defaultClassNameItem) {
                $matches = static::matchType($inValue, $defaultClassNameItem);
                if ($matches) $matchedClasses[] = $matches;
            }

            // If only 1 has matched, we use this definition class
            if (count($matchedClasses)==1) {
                return $matchedClasses[0];
            }
        }

        return null;
    }

    /**
     * Use the _type in value to detect if matches with an array of
     * classes given
     *
     * @param $inValue
     * @param $className
     * @return mixed
     */
    public static function matchType($inValue, $className) {
        if ($inValue instanceof \stdClass) {
            $inValueKeys = array_keys(get_object_vars($inValue));
            if (in_array('_type', $inValueKeys)) {
                $tempStore = new $className();
                $tempStoreType = $tempStore->get_type();
                if ($tempStoreType===$inValue->_type) return $className;
            }
        }

        return null;
    }

    /**
     * Check if the store class type is part of declared classes in
     * storeClasses.
     */
    public static function checkStoreClass($store, array $storeClasses, array $allowedId)
    {
        if (is_string($store) && strstr($store, '_')) {

            list($prefix, $id) = explode('_', $store);
            if (in_array($prefix, $allowedId)) {
                return true;
            }

            return false;
        }

        if (in_array(get_class($store), $storeClasses)) return true;
        return false;
    }

    /*
     * Automatically convert basic type to store
     */
    public static function simpleTypeToStore($inputData)
    {
        switch (gettype($inputData)) {
            case 'string':
                $store = new PBString();
                $store->setValue($inputData);

                return $store;
            case 'boolean':
                $store = new Boolean();
                $store->setValue($inputData);

                return $store;
        }

        return $inputData;
    }
}
