<?php

namespace PureMachine\Bundle\SDKBundle\Tests\Store\StoreClass;

use PureMachine\Bundle\SDKBundle\Store\Base\BaseStore;
use Symfony\Component\Validator\Constraints as Assert;
use PureMachine\Bundle\SDKBundle\Store\Annotation as Store;

class PrivateStore extends BaseStore
{
    /**
     * @Store\Property(description="testProperty A")
     * @Assert\Type("string")
     */
    protected $titleA;

    /**
     * @Store\Property(description="testProperty B", private=true)
     * @Assert\Type("string")
     */
    protected $titleB;
}
