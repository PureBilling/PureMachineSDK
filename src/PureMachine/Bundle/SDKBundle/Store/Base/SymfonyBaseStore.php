<?php
namespace PureMachine\Bundle\SDKBundle\Store\Base;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use PureMachine\Bundle\SDKBundle\Store\Annotation as Store;
use PureMachine\Bundle\SDKBundle\Exception\StoreException;
use PureMachine\Bundle\SDKBundle\Event\ResolveStoreEntityEvent;
/**
 * add modifiedProperties system
 */
abstract class SymfonyBaseStore extends BaseStore implements ContainerAwareInterface
{
    protected $doctrineEntityManager = null;
    protected $container = null;
    protected $entityCache = array();

    /**
     * Initialize the store from a array or a stdClass
     *
     * @param array or stdClass $data
     */
    protected function initialize($data)
    {
        parent::initialize($data);
    }

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;

        //Inject DoctrineEntityManager on all store childs that needs it
        $schema = static::getJsonSchema();
        foreach ($schema->definition as $propertyName=>$definition) {
            if ($this->$propertyName instanceof ContainerAwareInterface) {
                //Asking the child store if needs the doctrineEntityManager
                $this->$propertyName->setContainer($container);
            }
        }
    }

    /**
     * Set the doctrine entity Manager used
     * to resolve entities
     */
    public function setDoctrineEntityManager($entityManager)
    {
        $this->doctrineEntityManager = $entityManager;

        //Inject DoctrineEntityManager on all store childs that needs it
        $schema = static::getJsonSchema();
        foreach ($schema->definition as $propertyName=>$definition) {
            if ($this->$propertyName instanceof SymfonyBaseStore) {
                //Asking the child store if needs the doctrineEntityManager
                $this->$propertyName->setDoctrineEntityManager($entityManager);
            }
        }
    }

    public static function schemaBuilderHook($annotation, $property, array $definition)
    {
        if ($annotation instanceof Store\Entity)
            $definition[$property->getName()]->entity = $annotation->value;

        if ($annotation instanceof Store\EntityMapping) {
            if ($annotation->value == 'auto') {
                $definition[$property->getName()]->entityMapping = $property->getName();
            } else {
                $definition[$property->getName()]->entityMapping = $annotation->value;
            }
        }

        parent::schemaBuilderHook($annotation, $property, $definition);
    }

    /*
     * Automatically handle getter and setter functions
     * if not created
     *
     */
    public function __call($method, $arguments)
    {
        if ($method == 'getEntity' && $this->isStoreProperty('id')) {
            $method = 'getIdEntity';
        }

        $property = $this->getPropertyFromMethod($method);
        if (!$this->isStoreProperty($property)) {
            throw new \Exception("$method() method does not exits in " . get_class($this));
        }

        $methodPrefix = substr($method,0,3);
        $propertyExists = property_exists($this, $property);

        /**
         * Entity resoliver (Entity annotation)
         */
        if ($methodPrefix == 'get' && $propertyExists &&
            substr($method, strlen($method)-6) == 'Entity') {
            return $this->resolveEntity($property);
        }

        if ($methodPrefix == 'set') {
            if (($arguments[0] instanceof SymfonyBaseStore) && ($this->doctrineEntityManager)) {
                $this->$property->setDoctrineEntityManager($this->doctrineEntityManager);
            }
            if(count($arguments)==0) throw new StoreException("$method(\$value) takes 1 argument.");
        }

        /**
         * Entity synchronization (EntityMapping annotation)
         */
        if ($methodPrefix == 'set') {
            return $this->setEntityPropertyValue($method, $arguments, $property);
        }

        //Get the value from the entity
        //and define it to the store
        if ($methodPrefix == 'get')

            return $this->setStorePropertyValueFromEntity($property);

        return parent::__call($method, $arguments);
    }

    public function setEntity($entity)
    {
        $this->entityCache[$this->getId()] = $entity;
    }

    protected function setStorePropertyValueFromEntity($property)
    {
        $propSchema = static::getJsonSchema()->definition
                                             ->$property;
        $getter = "get" . ucfirst($property);

        if (!isset($propSchema->entityMapping))

            return parent::__call($getter, array());

        $entityMapping = $propSchema->entityMapping;
        $mappings = explode('.', $entityMapping);
        $entity = $this->getEntity();
        foreach ($mappings as $mapping) {
            $entityGetter = "get" . ucfirst($mapping);
            $entity = $entity->$entityGetter();
            if (!$entity) return parent::__call($getter, array());
        }
        $setter = "set" . ucfirst($property);
        parent::__call($setter, array($entity));

        return parent::__call($getter, array());
    }

    protected function setEntityPropertyValue($method, $arguments, $property)
    {
        parent::__call($method, $arguments);
        $value = $this->$property;

        if (!$this->isStoreProperty($property))

            return $this;

        $propSchema = static::getJsonSchema()->definition
                                             ->$property;
        if (!isset($propSchema->entityMapping))

            return $this;

        $entityMapping = $propSchema->entityMapping;
        $mappings = explode('.', $entityMapping);
        $entity = $this->getEntity();
        $entitySetterProperty = array_pop($mappings);
        if (count($mappings) > 0) {
            foreach ($mappings as $mapping) {
                $entityGetter = "get" . ucfirst($mapping);
                $entity = $entity->$entityGetter();
            }
        }

        $setter = "set" . ucfirst($entitySetterProperty);
        //Need to check for readonly method
        if (method_exists($entity, $setter)) $entity->$setter($value);

        return $this;
    }

    protected function resolveEntity($propertyName)
    {
        $id = $this->$propertyName;

        if ($id instanceof BaseStore) $id = $id->getId();

        if (array_key_exists($id, $this->entityCache))

                return $this->entityCache[$id];

        if (!is_scalar($id))
            throw new StoreException("Can't resolve entity $propertyName. id is an " . gettype($id)
                                     ." for " .$this->getClassName().".$propertyName ");

        $schema = static::getJsonSchema();

        //Check if the repostory is defined
        if (!$id) return $id;
        if (!isset($schema->definition->$propertyName->entity)) {
            throw new StoreException("to use entity resolution with "
                                    .$this->getClassName().".$propertyName "
                                    ."You need to add @Entity annotation",
                                     StoreException::STORE_004);
        }
        $repository = $schema->definition->$propertyName->entity;

        if ($repository == 'auto') {
            //Check if the entity manager has been defined
            if (!$this->container)
                throw new StoreException("To resolve ".$this->getClassName()
                                         .".$propertyName as entity, you need "
                                         ."to define the symfony container using "
                                         ."setContainer() method",
                                          StoreException::STORE_004);

            $event = new ResolveStoreEntityEvent();
            $event->setStore($this, $propertyName, $id);
            $dispatcher = $this->container->get('event_dispatcher');
            $dispatcher->dispatch('pureMachine.store.resolveEntity', $event);

            $entity = $event->getEntity();
            if ($entity) {
                $this->entityCache[$id] = $entity;

                return $entity;
            }
        }

        //If entity is set to auto, and resolution has not been done throw an event
        if ($repository == 'auto')
            throw new StoreException("You need to define a valid repository "
                                    ."for "
                                    .$this->getClassName()
                                    .".$propertyName in @Entity annotation ",
                                     StoreException::STORE_004);

        //Check if the entity manager has been defined
        if (!$this->doctrineEntityManager)
            throw new StoreException("To resolve $propertyName as entity, you need "
                                              ."to define the doctrine entity manager using "
                                              ."setDoctrineEntityManager() method",
                                              StoreException::STORE_004);

        try {
            $entity = $this->doctrineEntityManager
                           ->getRepository($repository)
                           ->find($id);
        } catch (\Exception $e) {
            throw new StoreException("entity '$id' in repository '$repository' not "
                                              ."found for property $propertyName",
                                              StoreException::STORE_004);
        }

        if (!$entity)
            throw new StoreException("entity '$id' in repository '$repository' not "
                                              ."found for property $propertyName",
                                              StoreException::STORE_004);
        $this->entityCache[$id] = $entity;

        return $entity;
    }

    public function validate($validator = null)
    {
        /**
         * Need to synchronize entity back to store
         */
        $schema = static::getJsonSchema();
        foreach ($schema->definition as $propertyName=>$definition) {
            $this->setStorePropertyValueFromEntity($propertyName);
        }

        return parent::validate($validator);
    }
}
