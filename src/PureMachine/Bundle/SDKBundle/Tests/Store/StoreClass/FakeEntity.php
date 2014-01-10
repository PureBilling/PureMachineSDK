<?php

namespace PureMachine\Bundle\SDKBundle\Tests\Store\StoreClass;

use PureMachine\Bundle\SDKBundle\Store\Annotation as Store;

class FakeEntity
{
    protected $id;
    protected $title;
    protected $autoMapping;
    protected $sub;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getAutoMapping()
    {
        return $this->autoMapping;
    }

    public function setAutoMapping($auto)
    {
        $this->autoMapping = $auto;
    }

    public function getSub()
    {
        if (!$this->sub) $this->sub = new FakeEntity();
        return $this->sub;
    }

    public function setSub($sub)
    {
        $this->sub = $sub;
    }
}
