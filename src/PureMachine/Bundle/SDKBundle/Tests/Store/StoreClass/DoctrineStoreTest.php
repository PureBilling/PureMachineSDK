<?php

namespace PureMachine\Bundle\SDKBundle\Tests\Store\StoreClass;

use PureMachine\Bundle\SDKBundle\Store\Base\SymfonyBaseStore;
use Symfony\Component\Validator\Constraints as Assert;
use PureMachine\Bundle\SDKBundle\Store\Annotation as Store;

class DoctrineStoreTest extends SymfonyBaseStore
{
    /**
     * @Store\Property(description="testProperty")
     * @Store\Entity("BundleName:EntityName")
     * @Assert\Type("string")
     */
    protected $title;
}
