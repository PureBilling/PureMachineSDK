<?php

namespace PureMachine\Bundle\SDKBundle\Tests\Store\StoreClass;

use PureMachine\Bundle\SDKBundle\Store\Base\BaseStore;
use Symfony\Component\Validator\Constraints as Assert;
use PureMachine\Bundle\SDKBundle\Store\Annotation as Store;

class StoreInStore extends BaseStore
{
    /**
     * @Store\Property(description="store in store")
     * @Assert\Type("object")
     * @Store\StoreClass({"PureMachine\Bundle\SDKBundle\Tests\Store\StoreClass\StoreA"})
     */
    protected $storeA;

    /**
     * @Store\Property(description="store in store")
     * @Assert\Type("array")
     * @Store\StoreClass({"PureMachine\Bundle\SDKBundle\Tests\Store\StoreClass\StoreA"})
     */
    protected $storesA;
}
