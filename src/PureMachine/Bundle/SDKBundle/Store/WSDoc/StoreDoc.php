<?php
namespace PureMachine\Bundle\SDKBundle\Store\WSDoc;

use Symfony\Component\Validator\Constraints as Assert;
use PureMachine\Bundle\SDKBundle\Store\Annotation as Store;
use PureMachine\Bundle\SDKBundle\Store\Base\BaseStore;

class StoreDoc extends ObjectPropertyDoc
{
    /**
     * @Store\Property(description="class of store in the value is a store")
     * @Assert\Type("string")
     * @Assert\NotBlank()
     */
    protected $class;
}
