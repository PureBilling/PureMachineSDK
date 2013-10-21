<?php

namespace PureMachine\Bundle\SDKBundle\Tests\Store\StoreClass;

use PureMachine\Bundle\SDKBundle\Store\Base\BaseStore;
use Symfony\Component\Validator\Constraints as Assert;
use PureMachine\Bundle\SDKBundle\Store\Annotation as Store;

class StoreABArray extends BaseStore
{
    /**
     * @Store\Property(description="testProperty")
     * @Assert\Type("array")
     * @Store\StoreClass({"PureMachine\Bundle\SDKBundle\Tests\Store\StoreClass\StoreA",
     *                   "PureMachine\Bundle\SDKBundle\Tests\Store\StoreClass\StoreB"})
     */
    protected $store;
}
