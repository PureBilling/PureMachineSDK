<?php

namespace PureMachine\Bundle\SDKBundle\Tests\Store\StoreClass;

use PureMachine\Bundle\SDKBundle\Store\Base\BaseStore;
use Symfony\Component\Validator\Constraints as Assert;
use PureMachine\Bundle\SDKBundle\Store\Annotation as Store;

class StoreANotBlank extends BaseStore
{
    /**
     * @Store\Property(description="testProperty")
     * @Assert\Type("string")
     * @Assert\NotBLank()
     */
    protected $titleA;
}
