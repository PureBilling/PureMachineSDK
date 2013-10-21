<?php
namespace PureMachine\Bundle\SDKBundle\Store\Base;

use PureMachine\Bundle\SDKBundle\Store\Annotation as Store;

class SimpleType extends BaseStore
{
    public function __construct($data=null)
    {
        if ($data && !($data instanceof \stdClass)) {
            $data = array("value" => $data);
        }
        parent::__construct($data);
    }
}
