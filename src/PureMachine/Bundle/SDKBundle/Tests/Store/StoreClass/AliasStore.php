<?php

namespace PureMachine\Bundle\SDKBundle\Tests\Store\StoreClass;

use PureMachine\Bundle\SDKBundle\Store\Base\BaseStore;
use Symfony\Component\Validator\Constraints as Assert;
use PureMachine\Bundle\SDKBundle\Store\Annotation as Store;

class AliasStore extends BaseStore
{
    /**
     * @Store\Property(description="test Property", alias="old_style_title")
     * @Assert\Type("string")
     */
    protected $title;

    /**
     * @Store\Property(description="test Property", alias="old_description")
     * @Assert\Type("string")
     */
    protected $description;

    /**
     * convert from old style to new style
     */
    public function setOld_description($data)
    {
        $this->description = strtoupper($data);
    }

    /**
     * convert from new style to old style
     */
    public function getOld_description()
    {
        return strtolower($this->description);
    }
}
