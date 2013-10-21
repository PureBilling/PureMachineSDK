<?php
namespace PureMachine\Bundle\SDKBundle\Store\WebService;

use Symfony\Component\Validator\Constraints as Assert;
use PureMachine\Bundle\SDKBundle\Store\Annotation as Store;

class ArrayResponse extends Response
{
    /**
     * @Store\Property(description="webService answer")
     * @Store\StoreClass("PureMachine\Bundle\SDKBundle\Store\Base\BaseStore")
     * @Assert\Type("array")
     * @Assert\NotBlank
     */
    protected $answer;

}
