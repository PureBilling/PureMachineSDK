<?php
namespace PureMachine\Bundle\SDKBundle\Store\Type;

use Symfony\Component\Validator\Constraints as Assert;
use PureMachine\Bundle\SDKBundle\Store\Annotation as Store;
use PureMachine\Bundle\SDKBundle\Store\Base\SimpleType;

class String extends SimpleType
{
    /**
     * @Store\Property(description="String generic store")
     * @Assert\Type("string")
     * @Assert\NotBlank
     */
    protected $value;
}
