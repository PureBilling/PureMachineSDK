<?php

namespace PureMachine\Bundle\SDKBundle\Tests\Store\StoreClass;

use PureMachine\Bundle\SDKBundle\Store\Base\BaseStore;
use PureMachine\Bundle\SDKBundle\Store\Annotation as Store;
use PureBilling\Bundle\SDKBundle\Constraints as PBAssert;

class StoreDateTime extends BaseStore
{
    /**
     * @Store\Property(description="test dateTime")
     * @PBAssert\Type(type="datetime")
     */
    protected $value;
}
