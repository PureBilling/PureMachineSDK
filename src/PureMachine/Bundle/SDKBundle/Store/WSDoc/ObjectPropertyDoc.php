<?php
namespace PureMachine\Bundle\SDKBundle\Store\WSDoc;

use Symfony\Component\Validator\Constraints as Assert;
use PureMachine\Bundle\SDKBundle\Store\Annotation as Store;

class ObjectPropertyDoc extends LiteralPropertyDoc
{

    /**
     * @Store\Property(description="Store documentation that can be used for the current property")
     * @Store\StoreClass("PureMachine\Bundle\SDKBundle\Store\WSDoc\StoreDoc")
     * @Assert\Type("array")
     */
    protected $children;
}
