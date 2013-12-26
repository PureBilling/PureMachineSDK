<?php
namespace PureMachine\Bundle\SDKBundle\Store\Type;

use Symfony\Component\Validator\Constraints as Assert;
use PureMachine\Bundle\SDKBundle\Store\Annotation as Store;
use PureMachine\Bundle\SDKBundle\Store\Base\SimpleType;

class Boolean extends SimpleType
{
    /**
     * @Store\Property(description="Boolean generic store")
     * @Assert\Type("boolean");
     */
    protected $value;
}
