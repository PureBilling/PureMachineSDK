<?php
namespace PureMachine\Bundle\SDKBundle\Store\Type;

use Symfony\Component\Validator\Constraints as Assert;
use PureMachine\Bundle\SDKBundle\Store\Annotation as Store;
use PureMachine\Bundle\SDKBundle\Store\Base\SimpleType;

class SArray extends SimpleType
{
    /**
     * @Store\Property(description="Array generic store")
     * @Assert\Type("array")
     * @Assert\NotBlank
     */
    protected $value;
}
