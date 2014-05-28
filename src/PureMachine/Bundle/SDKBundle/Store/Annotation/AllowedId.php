<?php
namespace PureMachine\Bundle\SDKBundle\Store\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
class AllowedId extends Annotation
{
    /** @var string @Required */
    public $value;
}
