<?php
namespace PureMachine\Bundle\SDKBundle\Store\WSDoc;

use Symfony\Component\Validator\Constraints as Assert;
use PureMachine\Bundle\SDKBundle\Store\Annotation as Store;
use PureMachine\Bundle\SDKBundle\Store\Base\BaseStore;

class LiteralPropertyDoc extends BaseStore
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
     * @Store\Property(description="Property description")
     * @Assert\Type("string")
     */
    protected $description;

    /**
     * @Store\Property(description="List of validation constraint classes")
     * @Assert\Type("array")
     */
    protected $validationConstraints;

    /**
     * @Store\Property(description="true if the property is required")
     * @Assert\Type("boolean")
     */
    protected $required = false;
}
