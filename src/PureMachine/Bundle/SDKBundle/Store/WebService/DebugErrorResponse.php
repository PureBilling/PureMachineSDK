<?php
namespace PureMachine\Bundle\SDKBundle\Store\WebService;

use Symfony\Component\Validator\Constraints as Assert;
use PureMachine\Bundle\SDKBundle\Store\Annotation as Store;

class DebugErrorResponse extends ErrorResponse
{
    /**
     * @Store\Property(description="Rebuild webService URL call with data")
     * @Assert\Type("string")
     */
    protected $url;
}
