<?php
namespace PureMachine\Bundle\SDKBundle\Store\Type;

use PureMachine\Bundle\SDKBundle\Store\Base\SimpleType;
use Symfony\Component\Validator\Constraints as Assert;
use PureMachine\Bundle\SDKBundle\Store\Annotation as Store;

class PBString extends SimpleType
{
    /**
     * @Store\Property(description="String generic store")
     * @Assert\Type("string")
     * @Assert\NotBlank
     */
    protected $value;
}
