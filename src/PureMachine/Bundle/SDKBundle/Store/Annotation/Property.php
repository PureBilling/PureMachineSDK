<?php
namespace PureMachine\Bundle\SDKBundle\Store\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
class Property extends Annotation
{
    public $description = 'default description';
    public $private = false;
    public $recommended = false;
    public $alias = null;

    /**
     * Property is not removed as serialization if null
     * @var bool
     */
    public $keepIfNull = false;
}
