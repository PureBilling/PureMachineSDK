<?php
namespace PureMachine\Bundle\SDKBundle\Store\WSDoc;

use Symfony\Component\Validator\Constraints as Assert;
use PureMachine\Bundle\SDKBundle\Store\Annotation as Store;
use PureMachine\Bundle\SDKBundle\Store\Base\BaseStore;

class WSInputOutputValueDoc extends BaseStore
{
    /**
     * @Store\Property(description="Property Name")
     * @Assert\Type("string")
     * @Assert\NotBlank()
     */
    protected $name;

    /**
     * @Store\Property(description="Property Type")
     * @Assert\Type("string")
     * @Assert\NotBlank()
     */
    protected $type;

    /**
     * @Store\Property(description="class of store in the value is a store")
     * @Assert\Type("string")
     * @Assert\NotBlank()
     */
    protected $class;

    /**
     * @Store\Property(description="Store documentation that can be used for the current property")
     * @Store\StoreClass("PureMachine\Bundle\SDKBundle\Store\WSDoc\StoreDoc")
     * @Assert\Type("array")
     */
    protected $children;
}
