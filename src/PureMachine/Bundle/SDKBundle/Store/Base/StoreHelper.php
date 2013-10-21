<?php
namespace PureMachine\Bundle\SDKBundle\Store\Base;

/**
 * Class that centralize all method to convert and check data
 * injected inside a Store
 *
 * At long term view, this class will be the starting point to
 * kill JsonSchemaFactory.
 */
class StoreHelper
{
    private static $validator;

    public static function isInstanceOf($object, array $classArray)
    {
        foreach($classArray as $class)
            if ($object instanceof $class) return true;

        return false;
    }

    public static function serialize($inValue)
    {
        if ($inValue instanceof BaseStore) return $inValue->serialize();

        if (is_array($inValue)) {
                $value = array();
                foreach ($inValue as $key => $item) {
                        $value[$key] = static::serialize($item);
                }

                return $value;
        }

        return $inValue;
    }

    public static function unSerialize($inValue, array $defaultClassNames, $annotationReader=null)
    {
        if ($inValue instanceof BaseStore) return $inValue;

        if ($inValue instanceof \stdClass) {
            //If there is a StoreClass defined, we try to initialize it
            $storeClass = self::getStoreClass($inValue, $defaultClassNames);
            if ($storeClass) {
                $value = self::createClass($storeClass, $inValue, $annotationReader);
            } else $value = $inValue;
        } elseif (is_array($inValue)) {
            //If the property if an array of Store, we create it
            $value = array();
            foreach ($inValue as $key => $item) {
                    $value[$key] = static::unSerialize($item, $defaultClassNames);
            }
        } else $value = $inValue;

        return $value;
    }

    /**
     * Create a class if exists and not abstract
     */
    public static function createClass($class, $data, $annotationReader=null)
    {
        if (!class_exists($class)) return null;
        $ref = new \ReflectionClass($class);
        if ($ref->isAbstract()) return null;
        $store =  new $class($data);

        if ($annotationReader) $store->getAnnotationReader($annotationReader);

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
        if ($inValue && isset($inValue->className) && class_exists($inValue->className))

            return $inValue->className;

        //We take it from the array if there is only one
        if (count($defaultClassName) == 1 && class_exists($defaultClassName[0]))

            return $defaultClassName[0];

        return null;
    }

    /**
     * Check if the store class type is part of declared classes in
     * storeClasses.
     */
    public function checkStoreClass($store, array $storeClasses)
    {
        if (in_array(get_class($store), $storeClasses)) return true;

        return false;
    }
}
