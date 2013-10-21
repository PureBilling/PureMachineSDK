<?php

namespace PureMachine\Bundle\SDKBundle\Tests\Store\StoreClass;

use PureMachine\Bundle\SDKBundle\Store\Base\BaseStore;
use PureMachine\Bundle\SDKBundle\Store\Annotation as Store;
use Symfony\Component\Validator\Constraints as Assert;

class ComposedStore extends BaseStore
{
    /**
     * @Store\Property(description="myProperty")
     * @Assert\Type("string")
     */
    protected $myProperty;
}
