<?php

namespace PureMachine\Bundle\SDKBundle\Tests\Store\StoreClass;

use PureMachine\Bundle\SDKBundle\Store\Base\BaseStore;
use Symfony\Component\Validator\Constraints as Assert;
use PureMachine\Bundle\SDKBundle\Store\Annotation as Store;

class StoreWithStoreANotBlankIn extends BaseStore
{
    /**
     * @Store\Property(description="store in store")
     * @Assert\Type("object")
     * @Store\StoreClass({"PureMachine\Bundle\SDKBundle\Tests\Store\StoreClass\StoreANotBlank"})
     */
    protected $storeA;

    /**
     * @Store\Property(description="store in store")
     * @Assert\Type("object")
     * @Assert\NotBlank()
     * @Store\StoreClass({"PureMachine\Bundle\SDKBundle\Tests\Store\StoreClass\StoreA"})
     */
    protected $storeB;
}
