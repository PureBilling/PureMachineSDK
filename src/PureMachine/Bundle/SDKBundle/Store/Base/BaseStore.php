<?php
namespace PureMachine\Bundle\SDKBundle\Store\Base;

use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Constraints as Assert;

use PureMachine\Bundle\SDKBundle\Exception\StoreException;
use PureMachine\Bundle\SDKBundle\Store\Annotation as Store;

/**
 * Base class for store that does not have modifiedProperties system
 */
abstract class BaseStore implements JsonSerializable
{
    private static $jsonSchema = array();
    protected static $annotationReader = null;
    protected static $validator = null;

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
    protected $className;
    
    public function setClassName($class){}

    /**
     * Create a stoe Object
     *
     * @param any $data Initialize the store content. Support Array or stdClass
     */
    public function __construct($data=null)
    {
        $this->className = get_class($this);

        if (!$data) {
            $data = array();
        }

        $this->initialize($data);
    }

    public static function setAnnotationReader($annotationReader)
    {
        self::$annotationReader = $annotationReader;
    }

    public static function setValidator($validator)
    {
        self::$validator = $validator;
    }

    /**
     * Return validation violations
     *
     * need to run validate() bedore
     */
    public function getViolations()
    {
        if (!$this->isValidated())
            throw new StoreException("You must call validate() method before.",
                                      StoreException::STORE_002);

        return $this->violations;
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
        if (count($this->violations) > 0)
            throw new StoreException('Store validation error :' . $this->violations[0],
                                     StoreException::STORE_002);
    }

    /**
     * Serilize the Store
     */
    public function serialize($includePrivate=false)
    {
        $answer = array();
        $schema = $this->getJsonSchema();
        foreach ($schema->definition as $property => $definition) {
            $method = 'get' . ucfirst($property);

            if ($definition->private == false || $includePrivate) {
                $value = StoreHelper::serialize($this->$method(), $includePrivate);
                $answer[$property] = $value;
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

        $schema = $this->getJsonSchema();

        /**
         * Initialize data
         * Also create empty composed Store
         */

        foreach ($schema->definition as $property => $definition) {
            if(!$this->isStoreProperty($property)) continue;
            //Find the value if there is one.
            if (array_key_exists($property, $data))
                $value = $data[$property];
            else {
                //To get default values
                $value = $this->$property;
            }

            /**
             * unSerialize Value
             * Note that we unSerialize when $value is null in order to
             * create Store composed class if there is.
             */
            if (isset($definition->storeClasses)) $storeClasses = $definition->storeClasses;
            else $storeClasses = array();

            $storeClass = StoreHelper::getStoreClass (null, $storeClasses);

            if ($value) {
                $value = StoreHelper::unSerialize($value, $storeClasses,
                                                  self::$annotationReader);
            } elseif ($storeClass && $definition->type == 'object') {
                $value = StoreHelper::createClass($storeClass, $data);
            } elseif ($definition->type == 'array')
                $value = array();

            if (!is_null($value)) {
                $method = 'set' . ucfirst($property);
                $this->$method($value);
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
        //If function exists, we always call it.
        if (method_exists($this, $method)) {
            return call_user_func_array(array($this,$method),$arguments);
        }

        $property = $this->getPropertyFromMethod($method);
        if (!$this->isStoreProperty($property))
            throw new StoreException("$property is not a store property in "
                                    .get_class($this),
                                     StoreException::STORE_005);

        $methodPrefix = substr($method,0,3);
        $propertyExists = property_exists($this, $property);

        switch ($methodPrefix) {
            case 'get':
                if ($propertyExists) {
                    //Without arguments
                    if (count($arguments) == 0) return $this->$property;
                    //With one argument
                    elseif (count($arguments) == 1) {
                        $prop = &$this->$property;

                        return $prop[$arguments[0]];
                    } else throw new StoreException("$method(\$key=null) take 0 or 1 arguments.",
                                                    StoreException::STORE_005);
                }
                break;
            case 'set':
                if ($propertyExists) {
                    if (count($arguments) == 1) {
                        $this->$property = $arguments[0];

                        return $this;
                    } else throw new StoreException("$method(\$value) takes 1 argument.",
                                                    StoreException::STORE_005);
                }
                break;
           case 'add':
               //FIXME: Change tracking does not support array addition or deletion.
               if ($propertyExists) {
                   $arrayToAdd = &$this->$property;
                   if (count($arguments) == 1) $arrayToAdd[] = $arguments[0];
                   elseif (count($arguments) == 2) $arrayToAdd[$arguments[1]] = $arguments[0];
                   else throw new StoreException("$method(\$value, \$key=null) take 1 or two arguments.",
                                                 StoreException::STORE_005);
               }

               return $this;
               break;
           case 'default':
                break;
        }

        throw new \Exception("$method() method does not exits in " . get_class($this));
    }

    protected function isStoreProperty($property)
    {
        $schema = $this->getJsonSchema();

        return isset($property, $schema->definition->$property);
    }

    /**
     * Return the json schema store definition
     *
     * @return array
     */
    public static function getJsonSchema($annotationReader=null)
    {
        $class = get_called_class();
        if(array_key_exists($class, self::$jsonSchema)) return self::$jsonSchema[$class];

        if (!$annotationReader) $annotationReader = new AnnotationReader();
        $definition = array();

        $reflect = new \ReflectionClass($class);
        $props = $reflect->getProperties(\ReflectionProperty::IS_PROTECTED);

        foreach ($props as $prop) {
            $annotations = $annotationReader->getPropertyAnnotations($prop);
            $definition[$prop->getName()]  = new \stdClass();
            $definition[$prop->getName()]->storeClasses = array();

            foreach ($annotations AS $annotation) {
                if ($annotation instanceof Store\Property) {
                    $definition[$prop->getName()]->description = $annotation->description;
                    $definition[$prop->getName()]->private = $annotation->private;
                } elseif ($annotation instanceof Store\StoreClass) {
                    $definition[$prop->getName()]->storeClasses = (array) $annotation->value;
                } elseif ($annotation instanceof Assert\Type) {
                    $definition[$prop->getName()]->type = $annotation->type;
                }

                static::schemaBuilderHook($annotation, $prop, $definition);
            }
        }

        /**
         * Check the schema integrity.
         * When close to relase, should move this to a store
         */
        $error = StoreException::STORE_003;
        foreach ($definition as $prop => $propertyDefinition) {
            //Remove added properties that are not store property.
            if (!isset($propertyDefinition->description)) {
                unset($definition[$prop]);
                continue;
            }

            if (!isset($propertyDefinition->type))
                //throw new \Exception ("$class->$prop must have Assert\Type(\"type\") defined.");
                throw new StoreException("$class->$prop must have Assert\Type(\"type\") defined.", $error);
            if (!$propertyDefinition->type)
                throw new StoreException("$class->$prop must have Assert\Type(\"type\") defined.", $error);
        }

        self::$jsonSchema[$class] = new \stdClass();
        self::$jsonSchema[$class]->definition = (object) $definition;
        self::$jsonSchema[$class]->configuration = new \stdClass();
        self::$jsonSchema[$class]->configuration->className = $class;

        return self::$jsonSchema[$class];
    }

    /**
     * Hook function to help child class to extend the json Schema.
     */
    public static function schemaBuilderHook($annotation, $property, array $definition)
    {

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
    public function validate($validator=null)
    {
        //Resetting violations
        $this->violations = array();

        /*
         * All child stores should be validated
         */
        $jsonSchema = self::getJsonSchema();
        foreach ($jsonSchema->definition as $propertyName=>$prodSchema) {
            if ($prodSchema->type=="object") {
                if (!($this->$propertyName instanceof BaseStore)) continue;
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
            } elseif ($prodSchema->type=="array" && count($prodSchema->storeClasses) >0
                      && is_array($this->$propertyName)) {

                foreach ($this->$propertyName as $store) {
                    if (!StoreHelper::checkStoreClass($store, $prodSchema->storeClasses))
                            $this->addViolation($propertyName, "'$propertyName' array element has a wrong "
                                                              ."store type.");
                }
            }
        }

        if (!$validator) {
            if (self::$validator) $validator = self::$validator;
            else $validator = Validation::createValidatorBuilder()
                                     ->enableAnnotationMapping()
                                     ->getValidator();
        }

        $violationsFromValidation = $validator->validate($this);
        foreach($violationsFromValidation as $violation) $this->violations[] = $violation;

        $this->isValidated = true;

        if (count($this->violations)>0) return false;
        return true;
    }

    protected function getPropertyFromMethod($method)
    {
        //If the method ends with Entity, we remove it
        if (substr($method, strlen($method)-6) == 'Entity')
                $method = substr($method,0, strlen($method)-6);

        //Try with first letter as lowerCase
        $property = lcfirst(substr($method,3));
        if (property_exists($this, $property)) return $property;

        //if does not exists, return without changing lowercase
        if (property_exists($this, substr($method,3))) return substr($method,3);
        return $property;
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
