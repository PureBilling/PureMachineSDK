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
}
