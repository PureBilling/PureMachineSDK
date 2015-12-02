<?php

namespace PureMachine\Bundle\SDKBundle\Tests\Store\StoreClass;

use PureMachine\Bundle\SDKBundle\Store\Base\BaseStore;
use Symfony\Component\Validator\Constraints as Assert;
use PureMachine\Bundle\SDKBundle\Store\Annotation as Store;

class StoreD extends BaseStore
{
    /**
     * @Store\Property(description="testProperty")
     * @Assert\Type("string")
     * @Assert\NotBlank
     */
    protected $titleD;

    /**
     * @Store\Property(description="testProperty")
     * @Assert\Type("string")
     */
    protected $nullableTitle;

    /**
     * @Store\Property(description="testProperty", keepIfNull=True)
     * @Assert\Type("string")
     */
    protected $nullableTitle2;
}
