<?php

namespace PureMachine\Bundle\StoreBundle\Store\Base;

use PureMachine\Bundle\StoreBundle\Adapter\CachedJsonSchemaInterface;
use PureMachine\Bundle\StoreBundle\Manager\StoreManager;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Constraints as Assert;
use PureMachine\Bundle\SDKBundle\Exception\StoreException;
use PureMachine\Bundle\SDKBundle\Store\Annotation as Store;
use Symfony\Component\Validator\Constraint;
use PureMachine\Bundle\SDKBundle\Store\Base\JsonSerializable;
use PureMachine\Bundle\SDKBundle\Store\Base\StoreHelper;

abstract class BaseStore implements JsonSerializable
{
    private static $jsonSchema = array();
    private $_adapter = null;
    private $_storePropertiesCache = array();
    protected $_jsonSchema = array();

    /**
     * Array of ConstraintViolation instances
     *
     * @var array
     */
    protected $violations = array();

    /**
     * Boolean flag indicator indicating if the store
     * has been validated or not
     *
     * @var boolean
     */
    protected $isValidated = false;

    /**
     * @Store\Property(description="Special property that contains the store class name.")
     * @Assert\Type("string")
     * @Assert\NotBlank
     */
    protected $_className;


    public function set_className($class) {}

    /**
     * Create a stoe Object
     *
     * @param any $data Initialize the store content. Support Array or stdClass
     */
    public function __construct($data=null)
    {
        $this->_className = get_class($this);
        $this->_adapter = StoreManager::getAdapter($this);

        //Instance caches
        $schema = $this->getJsonSchema();
        $this->_jsonSchema = $schema;
        $this->_storePropertiesCache = array_keys($schema['definition']);

        if (is_array($data) ||($data instanceof \stdClass)) {
            $this->initialize($data);
        } else {
            $this->initialize(array());
        }

        if ($this->_adapter) {
            $this->_adapter->hydrate($this, $data);
        }
    }

    /**
     * Handle serialize()
     * Need to remove the private and static properties before serialize a store
     *
     */
    public function __sleep()
    {
        $reflect = new \ReflectionClass($this);
        $props = $reflect->getProperties(\ReflectionProperty::IS_PUBLIC | \ReflectionProperty::IS_PROTECTED);
        $propsSerialize = array();
        foreach ($props as $prop) {
            $propsSerialize[] = $prop->getName();
        }

        return $propsSerialize;
    }

    /**
     * Handle unserialize()
     * Need to set the adapter object after unserialize a store
     *
     */
    public function __wakeup()
    {
        $this->_adapter = StoreManager::getAdapter($this);
        //Instance caches
        $schema = $this->getJsonSchema();
        $this->_jsonSchema = $schema;
        $this->_storePropertiesCache = array_keys($schema['definition']);

    }

    /**
     * @deprecated
     */
    public static function setAnnotationReader($annotationReader)
    {
        //Depreciated, use storeManager instread
    }

    /**
     * @deprecated
     */
    public static function getAnnotationReader()
    {
        return StoreManager::getAnnotationReader();
    }

    /**
     * @deprecated
     */
    public static function setValidator($validator)
    {
        //Depreciated, use storeManager instread
    }

    /**
     * Return validation violations
     *
     * need to run validate() bedore
     */
    public function getViolations($ignoredVioltations=null)
    {
        if (!$this->isValidated())
            throw new StoreException("You must call validate() method before.",
                StoreException::STORE_002);

        if (!is_array($ignoredVioltations)) {
            return $this->violations;
        }

        /**
         * Ignore violation by property path
         */
        $violations = array();
        foreach ($this->getViolations() as $violation) {
            if (!in_array($violation->getPropertyPath(), $ignoredVioltations)) {
                $violations[] = $violation;
            }
        }

        return $violations;
    }

    public function jsonSerialize()
    {
        return json_encode($this->serialize());
    }

    /*
     * Need to run validate before
     */
    public function raiseIfInvalid()
    {
        if (!$this->isValidated())
            throw new StoreException("You must call validate() method before.",
                StoreException::STORE_002);
        if (count($this->violations) > 0) {
            throw new StoreException('Store validation error :' . $this->violations[0],
                StoreException::STORE_002);
        }
    }

    /**
     * Serilize the Store
     *
     * if removeNullValues is True, non mandatory null values are removed from serialized store
     */
    public function serialize($includePrivate=false, $includeInternal=true, $removeNullValues=false,
                              $dateAsISO8601=false)
    {
        $answer = array();
        $schema = $this->_jsonSchema;

        foreach ($schema['definition'] as $property => $definition) {
            $method = 'get' . ucfirst($property);

            //Checking special types
            if ($definition['private'] == true && !$includePrivate) {
                continue;
            }

            if ($definition['internal'] == true && !$includeInternal) {
                continue;
            }

            $valueFromMethod = $this->$method();

            if (isset($definition['type'])) {
                switch ($definition['type']) {
                    case "datetime":
                        if ($dateAsISO8601 && !is_null($valueFromMethod)) {
                            $valueFromMethod = $valueFromMethod->format("c");
                        } else {
                            $valueFromMethod = $this->$property; //Integer value
                        }
                        break;
                }
            }
            $value = StoreHelper::serialize($valueFromMethod, $includePrivate, $includeInternal, $removeNullValues, $dateAsISO8601);

            if (is_null($value)
                && $removeNullValues
                && array_key_exists('validationConstraints', $definition)
                && !$definition['keepIfNull']
                && !(in_array('NotBlank', $definition['validationConstraints']))) {

                continue;
            }

            $answer[$property] = $value;

            /**
             * Add aliases
             */
            if ($definition['alias']) {
                $alias = $definition['alias'];
                $method = 'get' . ucfirst($alias);
                if (method_exists($this, $method)) {
                    $answer[$alias] = $this->$method();
                } else {
                    $answer[$alias] = $answer[$property];
                }
            }
        }

        return (object) $answer;
    }

    /**
     * Initialize the store from a array or a stdClass
     *
     * @param array or stdClass $data
     */
    protected function initialize($data)
    {
        //Force conversion to array
        if (is_object($data)) $data = (array) $data;
        $schema = $this->_jsonSchema;

        /*
        * Alias management
        */
        foreach ($schema['definition'] as $property => $definition) {
            $alias = $definition['alias'];
            if ($alias && array_key_exists($alias, $data)) {
                $method = 'set' . ucfirst($alias);
                if (method_exists($this, $method)) {
                    $data[$property] = $this->$method($data[$alias]);
                } else {
                    $data[$property] = $data[$alias];
                }
            }
        }

        /**
         * Initialize data
         * Also create empty composed Store
         */

        foreach ($schema['definition'] as $property => $definition) {
            if (!in_array($property, $this->_storePropertiesCache)) {
                continue;
            }

            if ($definition['internal']) {
                continue;
            }

            //Find the value if there is one.
            if (!is_array($data)) {
                if (is_string($data)) {
                    throw new StoreException(
                        "Error initializing the store. Expected array on init data but got string : '".$data."'"
                        . " for property $property"
                    );
                } elseif (is_bool($data)) {
                    $value = "true";
                    if(!$data) $value = "false";
                    throw new StoreException(
                        "Error initializing the store. Expected array on init data but got boolean with (".$data.") value"
                        . " for property $property"
                    );
                } elseif (is_numeric($data)) {
                    throw new StoreException(
                        "Error initializing the store. Expected array on init data but got a numeric value : ".$data
                        . " for property $property"
                    );
                } else {
                    throw new StoreException(
                        "Error initializing the store. Expected array on init data but got ".gettype($data)
                        . " for property $property"
                    );
                }
            }
            if (array_key_exists($property, $data))
                $value = $data[$property];
            else {
                //To get default values
                $value = $this->$property;
            }

            /**
             * if we receive a array, but it's defined as object, we convert it
             * If not, the cast to Store is not done
             */
            if ($definition['type'] == 'object' && is_array($value)) {
                $value = (object) $value;
                $data[$property] = $value;
            }

            /**
             * if we receive a array, but it's defined as object, we convert it
             * If not, the cast to Store is not done
             */
            if ($definition['type'] == 'array' && is_object($value)) {
                $value = (array) $value;
                $data[$property] = $value;
            }


            /**
             * unSerialize Value
             */
            if (isset($definition['storeClasses'])) $storeClasses = $definition['storeClasses'];
            else $storeClasses = array();

            $storeClass = StoreHelper::getStoreClass(null, $storeClasses);

            if ($value) {
                $value = StoreHelper::unSerialize($value, $storeClasses,
                    StoreManager::getAnnotationReader());
            } elseif ($storeClass && $definition['type'] == 'object') {

                /**
                 * We create a store with no values only if it's NotNone
                 */
                if (in_array('NotBlank', $definition['validationConstraints'])) {
                    $value = StoreHelper::createClass($storeClass, $data);
                }

            } elseif ($definition['type'] == 'array')
                $value = array();

            if (!is_null($value)) {
                $method = 'set' . ucfirst($property);
                $this->_setPropertyValue($property, $value);
            }
        }
    }

    /*
     * Automatically handle getter and setter functions
     * if not created
     *
     */
    public function __call($method, $arguments)
    {
        $methodPrefix = substr($method,0,3);

        /**
         * SomeTime, property are defined with a UpperCase, sometime not ...
         */
        if (property_exists($this, ucfirst(substr($method, 3)))) {
            $property = ucfirst(substr($method, 3));
        } else {
            $property = lcfirst(substr($method, 3));
        }
        $allowedPrefix = array('get', 'set', 'add');
        if (!in_array($methodPrefix, $allowedPrefix)) {
            throw new StoreException($method."() call is not a valid. "
                .get_class($this),
                StoreException::STORE_005);
        }

        switch ($methodPrefix) {
            case 'get':
                if (count($arguments)>0) {
                    $index = $arguments[0];
                } else {
                    $index = null;
                }
                if ($this->_adapter) {
                    return $this->_adapter->getPropertyValue($this, $property, $index);
                }

                return $this->_getPropertyValue($property, $index);
            case 'set':
                if (count($arguments)>0) {
                    $value = $arguments[0];
                } else {
                    throw new StoreException("$method() takes 1 argument.",
                        StoreException::STORE_005);
                }
                if ($this->_adapter) {
                    return $this->_adapter->setPropertyValue($this, $property, $value);
                }

                return $this->_setPropertyValue($property, $value);
            case 'add':
                if (!$this->isStoreProperty($property)) {
                    throw new StoreException("$property is not a store property in "
                        .get_class($this),
                        StoreException::STORE_005);
                }

                if (in_array($property, $this->_storePropertiesCache)) {
                    $arrayToAdd = &$this->$property;
                    if (count($arguments) == 1) {
                        $arrayToAdd[] = $arguments[0];
                    } elseif (count($arguments) == 2) {

                        if (!is_string($arguments[0])) {
                            throw new StoreException("key type is: " . gettype($arguments[0]), StoreException::STORE_007);
                        }

                        $arrayToAdd[$arguments[0]] = $arguments[1];
                    } else {
                        throw new StoreException("$method take two arguments :(\$key, \$value), or one: (\$value) ",
                            StoreException::STORE_005);
                    }
                }

                return $this;
                break;
            case 'default':
                break;
        }

        throw new \Exception("$method() method does not exits in " . get_class($this));
    }

    public function _getPropertyValue($property, $index=null)
    {
        //If function exists, we always call it.
        $method = "get" . ucfirst($property);
        if (method_exists($this, $method)) {
            return call_user_func_array(array($this,$method),array($index));
        }

        if (!in_array($property, $this->_storePropertiesCache)) {
            throw new StoreException("$property is not a store property in "
                .get_class($this),
                StoreException::STORE_005);
        }

        $propertyDefinition = $this->_jsonSchema['definition'][$property];

        //Special retrieving of properties
        if (isset($propertyDefinition) && isset($propertyDefinition['type'])) {
            //Checking the definition of the property
            switch ($propertyDefinition['type']) {
                case 'datetime':
                    if (is_numeric($this->$property)) {
                        $dt = new \DateTime();
                        $dt->setTimestamp($this->$property);

                        return $dt;
                        break;
                    }

                    return null;
                    break;
            }
        }

        if (!is_null($index)) {
            $prop = &$this->$property;

            return $prop[$index];
        }

        return $this->$property;
    }

    public function _setPropertyValue($property, $value)
    {
        //If function exists, we always call it.
        $method = "set" . ucfirst($property);
        if (method_exists($this, $method)) {
            return call_user_func_array(array($this,$method),array($value));
        }

        if (!in_array($property, $this->_storePropertiesCache)) {
            throw new StoreException("$property is not a store property in "
                .get_class($this),
                StoreException::STORE_005);
        }

        //Retrieving definition
        $propertyDefinition = $this->_jsonSchema['definition'][$property];

        //Special setting of properties
        if ($value && isset($propertyDefinition) && isset($propertyDefinition['type'])) {
            //Checking the definition of the property
            switch ($propertyDefinition['type']) {
                case 'datetime':
                    if (is_numeric($value) && ($value>0)) {
                        //Auto conversion for numeric values into datetime as Unix timestamp
                        $this->$property = (int) $value;

                        return $this;
                    }
                    elseif (is_string($value)) {

                        try {
                            $dt = new \DateTime($value);
                         } catch (\Exception $e) {
                            throw new StoreException("$property date string format is invalid: " . $value .
                                ". error is " . $e->getMessage(),
                                StoreException::STORE_005);
                        }

                        ;
                        $this->$property = $dt->format("U");

                        return $this;
                    }

                    if (!$value instanceof \DateTime) {
                        throw new StoreException("$property only accepts DateTime as input date , got " . gettype($value),
                            StoreException::STORE_005);
                    }
                    //Set the value as unix timestamp
                    $this->$property = (int) $value->format("U");

                    return $this;
                    break;
                case 'integer':
                    if (is_numeric($value)) {
                        $this->$property = (int) $value;
                    } else {
                        $this->$property = $value;
                    }

                    return $this;
                    break;
                case 'float':
                    if (is_numeric($value)) {
                        $this->$property = (float) $value;
                    } else {
                        $this->$property = $value;
                    }

                    return $this;
                    break;
            }
        }

        $this->$property = $value;

        return $this;
    }

    public function isStoreProperty($property)
    {
        return in_array($property, $this->_storePropertiesCache);
    }

    /**
     * Return the json schema store definition
     *
     * @return array
     */
    public static function getJsonSchema($deprecated=null)
    {
        $class = get_called_class();

        /**
         * Look in local cache
         */
        if (array_key_exists($class, self::$jsonSchema)) {
            return self::$jsonSchema[$class];
        }

        /**
         * If not in local cache, we ask to the adaptor
         */
        $adapter = StoreManager::getAdapter($class);
        if ($adapter && ($adapter instanceof CachedJsonSchemaInterface )) {
            $schema = $adapter->getJsonSchema($class);
            if ($schema) {
                if (!array_key_exists($class, self::$jsonSchema)) {
                    self::$jsonSchema[$class] = $schema;
                }

                return $schema;
            }
        }

        $annotationReader = StoreManager::getAnnotationReader();
        $definition = array();

        $reflect = new \ReflectionClass($class);
        $props = $reflect->getProperties(\ReflectionProperty::IS_PROTECTED);

        foreach ($props as $prop) {
            $property = $prop->getName();
            $annotations = $annotationReader->getPropertyAnnotations($prop);
            $definition[$property]  = array();
            $definition[$property]['storeClasses'] = array();
            $definition[$property]['allowedId'] = array();
            $definition[$property]['validationConstraints'] = array();

            foreach ($annotations as $annotation) {
                if ($annotation instanceof Store\Property) {
                    $definition[$property]['description'] = $annotation->description;
                    $definition[$property]['keepIfNull'] = $annotation->keepIfNull;
                    $definition[$property]['private'] = $annotation->private;

                    if (substr($property,0,1) == "_") {
                        $definition[$property]['internal'] = true;
                    } else {
                        $definition[$property]['internal'] = false;
                    }

                    $definition[$property]['alias'] = $annotation->alias;
                    $definition[$property]['recommended'] = $annotation->recommended;
                } elseif ($annotation instanceof Store\StoreClass) {
                    $definition[$property]['storeClasses'] = (array) $annotation->value;
                } elseif ($annotation instanceof Assert\Type) {
                    $definition[$property]['type'] = $annotation->type;
                } elseif ($annotation instanceof Constraint) {
                    $definition[$property]['validationConstraints'][] = static::getClassName($annotation);
                } elseif ($annotation instanceof Store\AllowedId) {
                    $definition[$property]['allowedId'] = (array) $annotation->value;
                }

                if ($adapter) {
                    $newDef = $adapter->extendStoreSchema($annotation, $property, $definition);
                    if (is_array($newDef)) {
                        $definition[$property] = array_merge($newDef, $definition[$property]);
                    }
                }
            }
        }

        /**
         * Check the schema integrity.
         * When close to relase, should move this to a store
         */
        $error = StoreException::STORE_003;
        foreach ($definition as $prop => $propertyDefinition) {
            //Remove added properties that are not store property.
            if (!isset($propertyDefinition['description'])) {
                unset($definition[$prop]);
                continue;
            }

            if (!isset($propertyDefinition['type']))
                //throw new \Exception ("$class->$prop must have Assert\Type(\"type\") defined.");
                throw new StoreException("$class->$prop must have Assert\Type(\"type\") defined.", $error);
            if (!$propertyDefinition['type'])
                throw new StoreException("$class->$prop must have Assert\Type(\"type\") defined.", $error);
        }

        self::$jsonSchema[$class] = array();
        self::$jsonSchema[$class]['definition'] = $definition;
        self::$jsonSchema[$class]['configuration'] = array();
        self::$jsonSchema[$class]['configuration']['_className'] = $class;

        if ($adapter && $adapter instanceof CachedJsonSchemaInterface) {
            $adapter->cacheJsonSchema($class, self::$jsonSchema[$class]);
        }

        return self::$jsonSchema[$class];
    }

    protected static function getClassName($class)
    {
        if (is_object($class)) {
            $class = get_class($class);
        }

        $class = explode('\\', $class);

        return end($class);
    }

    public function addViolation($path, $message)
    {
        $this->violations[] = new ConstraintViolation(
            $message,
            $message,
            array(),
            null,
            (is_null($path)) ? "" : $path,
            null
        );
    }

    public function getFirstErrorString()
    {
        if (count($this->violations) == 0) {
            return null;
        }

        $violation = $this->violations[0];
        $object = $this->_shortenStoreClassName(get_class($this));
        $property = $violation->getPropertyPath();
        $message = $violation->getMessage();
        $value = $violation->getInvalidValue();
        $valueType = gettype($violation->getInvalidValue());


        if (is_null($value)) {
            $valueString = "value is null";
        } else {
            $valueString = "value(type:$valueType)";
            if (is_scalar($value)) {
                $valueString .= "=" . $value;
            }
        }

        return "'$property' validation error: $message $valueString in '$object'";
    }

    protected function _shortenStoreClassName($storeClassName)
    {
        if (strstr($storeClassName, 'V3')) {
            $parts = explode('V3', $storeClassName);
            return str_replace('\\', '/', 'V3' . $parts[1]);
        }

        if (strstr($storeClassName, 'V1')) {
            $parts = explode('V1', $storeClassName);
            return str_replace('\\', '/', 'V1' . $parts[1]);
        }

        return $storeClassName;
    }

    /**
     * Returns true if the store has been validated with
     * validate() method
     *
     * @return boolean
     */
    public function isValidated()
    {
        return $this->isValidated;
    }

    /**
     * validate the store with the restrictions
     * defined with property annotations
     *
     * @return boolean : True if valid
     */
    public function validate($deprecated=null)
    {
        //Resetting violations
        $this->violations = array();

        if ($this->_adapter) {
            $violations = $this->_adapter->validate($this);
            if (is_array($violations) && count($violations) > 0) {
                $this->isValidated = true;

                return false;
            }
        }

        /*
         * All child stores should be validated
         */
        $jsonSchema = $this->_jsonSchema;
        foreach ($jsonSchema['definition'] as $propertyName=>$prodSchema) {
            if ($prodSchema['type']=="object" || $prodSchema['type']=="id") {
                if ($prodSchema['type']=="id" && is_scalar($this->$propertyName)) continue;

                /**
                 * we enter here if we are not able to unserialize the object
                 * because it does not have a valid _className property, or/and there
                 * is two StoreClasses possible
                 */
                if ($this->$propertyName instanceof \stdClass && count($prodSchema['storeClasses'])) {

                    if (isset($this->$propertyName->_className)) {
                        $className = $this->$propertyName->_className;
                        /**
                         * the _className class does not exists
                         */
                        if (!class_exists($className)) {
                            $this->addViolation($propertyName, "$propertyName ->_className '$className' store class does not exists ");
                        }

                        if (!in_array($className, $prodSchema['storeClasses'])) {
                            $this->addViolation($propertyName, "$propertyName ->_className is not allowed here");
                        }
                    }

                    $this->addViolation($propertyName, "$propertyName should define _className property. see store definition");
                }

                if ((!$this->$propertyName instanceof BaseStore)) continue;

                $validationChild = $this->$propertyName->validate();
                if (!$validationChild) {
                    $violationsChild = $this->$propertyName->getViolations();
                    foreach ($violationsChild as $violation) {
                        /*
                         * Adding violations with propertyName as prefix on the
                         * violations of the parent
                         */
                        $this->violations[] = new ConstraintViolation(
                            $violation->getMessage(),
                            $violation->getMessageTemplate(),
                            $violation->getMessageParameters(),
                            $violation->getRoot(),
                            $propertyName . "." . $violation->getPropertyPath(),
                            $violation->getInvalidValue(),
                            $violation->getMessagePluralization(),
                            $violation->getCode()
                        );
                    }
                }
            } elseif ($prodSchema['type']=="array" && count($prodSchema['storeClasses']) >0
                && is_array($this->$propertyName)) {

                foreach ($this->$propertyName as $store) {
                    if (!StoreHelper::checkStoreClass($store, $prodSchema['storeClasses'], $prodSchema['allowedId'])) {
                        $this->addViolation($propertyName, "'$propertyName' array element has a wrong "
                            ."store type.");
                    }
                }
            }
        }

        $violationsFromValidation = StoreManager::getValidator()->validate($this);
        foreach($violationsFromValidation as $violation) $this->violations[] = $violation;

        $this->isValidated = true;

        if (count($this->violations)>0) return false;
        return true;
    }

    public function __toString()
    {
        try {
            return print_r($this->serialize(true), true);
        } catch (\Exception $e) {
            return "Exception raised: " . $e->getMessage();
        }
    }
}
