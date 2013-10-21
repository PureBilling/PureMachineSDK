<?php
namespace PureMachine\Bundle\SDKBundle\Store;

use Symfony\Component\Validator\Constraints as Assert;
use PureMachine\Bundle\SDKBundle\Store\Annotation as Store;

class LogStore extends Base\BaseStore
{
    /**
     * @Store\Property(description="Short title about the log")
     * @Assert\Type("string")
     * @Assert\NotBlank
     */
    protected $title;

    /**
     * @Store\Property(description="log messages")
     * @Assert\Type("array")
     * @Assert\NotBlank
     */
    protected $messages = array();

    public function addMessage($key, $value)
    {
        $this->message[$key] = $value;
    }
}
