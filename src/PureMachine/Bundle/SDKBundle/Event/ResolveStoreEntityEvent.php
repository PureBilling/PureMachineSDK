<?php

namespace PureMachine\Bundle\SDKBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use PureMachine\Bundle\SDKBundle\Store\Base\SymfonyBaseStore;

/**
 * trigger a puremachine.store.resolve_entity event when SymfonyBaseStore
 * try to resolve a store property to an entity.
 */
class ResolveStoreEntityEvent extends Event
{
    private $store;
    private $property;
    private $value;
    private $entity;

    public function setStore(SymfonyBaseStore $store, $property, $value)
    {
        $this->store = $store;
        $this->property = $property;
        $this->value = $value;
    }

    public function getEntity()
    {
        return $this->entity;
    }

    public function setEntity($entity)
    {
        $this->entity = $entity;
    }

    public function getPropertyName()
    {
        return $this->property;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function getStore()
    {
        return $this->store;
    }
}
