<?php
namespace PureMachine\Bundle\SDKBundle\Store\WSDoc;

use Symfony\Component\Validator\Constraints as Assert;
use PureMachine\Bundle\SDKBundle\Store\Annotation as Store;
use PureMachine\Bundle\SDKBundle\Store\Base\BaseStore;

class WSReference extends BaseStore
{
    /**
     * @Store\Property(description="webService Name")
     * @Assert\Type("string")
     * @Assert\NotBlank()
     */
    protected $name;

    /**
     * @Store\Property(description="webService Version")
     * @Assert\Type("string")
     * @Assert\NotBlank()
     */
    protected $version = 'V1';

    /**
     * @Store\Property(description="webService Description")
     * @Assert\Type("string")
     */
    protected $description;

    /**
     * @Store\Property(description="webService input value(s)")
     * @Store\StoreClass("PureMachine\Bundle\SDKBundle\Store\WSDoc\WSInputOutputValueDoc")
     * @Assert\Type("array")
     */
    protected $inputTypes;

    /**
     * @Store\Property(description="webService return value(s)")
     * @Store\StoreClass("PureMachine\Bundle\SDKBundle\Store\WSDoc\WSInputOutputValueDoc")
     * @Assert\Type("array")
     */
    protected $returnTypes;
}
