<?php
namespace PureMachine\Bundle\SDKBundle\Store\WebService;

use Symfony\Component\Validator\Constraints as Assert;
use PureMachine\Bundle\SDKBundle\Store\Annotation as Store;
use PureMachine\Bundle\SDKBundle\Store\Base\BaseStore;

class Response extends BaseStore
{
    /**
     * @Store\Property(description="webService name that has generated the answer")
     * @Assert\Type("string")
     * @Assert\NotBlank
     */
    protected $webService;

    /**
     * @Store\Property(description="webService version")
     * @Assert\Type("string")
     * @Assert\NotBlank
     */
    protected $version;

    /**
     * @Store\Property(description="webService response status")
     * @Assert\Type("string")
     * @Assert\NotBlank
     * @Assert\Choice({"error", "success"})
     */
    protected $status;

    /**
     * @Store\Property(description="webService resolution has been done in local")
     * @Assert\Type("boolean")
     */
    protected $local;

    /**
     * @Store\Property(description="webService answer")
     * @Store\StoreClass("PureMachine\Bundle\SDKBundle\Store\Base\BaseStore")
     * @Assert\Type("object")
     * @Assert\NotBlank
     */
    protected $answer;
}
