<?php

namespace PureMachine\Bundle\SDKBundle\Tests\Store\StoreClass;

use PureMachine\Bundle\SDKBundle\Store\Base\BaseStore;
use Symfony\Component\Validator\Constraints as Assert;
use PureMachine\Bundle\SDKBundle\Store\Annotation as Store;

class TestStore extends BaseStore
{
    /**
     * @Store\Property(description="testProperty")
     * @Assert\Type("string")
     * @Assert\NotBlank
     */
    protected $testProperty;

    /**
     * @Store\Property(description="propertyWithCustomGetter")
     * @Assert\Type("string")
     * @Assert\NotBlank
     */
    protected $propertyWithCustomGetter;

    /**
     * @Store\Property(description="propertyWithCustomSetter")
     * @Assert\Type("string")
     */
    protected $propertyWithCustomSetter;

    /**
     * @Store\Property(description="simpleArray")
     * @Assert\Type("array")
     * @Assert\NotBlank
     */
    protected $simpleArray;

    /**
     * @Store\Property(description="propertyWithCustomSetter")
     * @Assert\Type("object")
     * @Store\StoreClass("PureMachine\Bundle\SDKBundle\Tests\Store\StoreClass\ComposedStore");
     * @Assert\NotBlank
     */
    protected $composedStore;

    /**
     * @Store\Property(description="propertyWithCustomSetter")
     * @Assert\Type("array")
     * @Store\StoreClass("PureMachine\Bundle\SDKBundle\Tests\Store\StoreClass\ComposedStore");
     * @Assert\NotBlank
     */
    protected $arrayOfComposedStore;

    public function getPropertyWithCustomGetter()
    {
        return "getter is forcing the value";
    }

    public function setPropertyWithCustomSetter($value)
    {
        $this->propertyWithCustomSetter = "setter is forcing the value";

        return $this;
    }

    public function getTest_property()
    {
        return $this->testProperty . " from function";
    }

    public function setTest_property($value)
    {
        return $this->testProperty = $value . " from function";;
    }
}
