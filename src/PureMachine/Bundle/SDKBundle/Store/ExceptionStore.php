<?php
namespace PureMachine\Bundle\SDKBundle\Store;

use Symfony\Component\Validator\Constraints as Assert;
use PureMachine\Bundle\SDKBundle\Store\Annotation as Store;

class ExceptionStore extends Base\BaseStore
{
    /**
     * @Store\Property(description="Exception generic message")
     * @Assert\Type("string")
     * @Assert\NotBlank
     */
    protected $message;

    /**
     * @Store\Property(description="Exception error code.")
     * @Assert\Type("string")
     * @Assert\NotBlank
     */
    protected $code;

    /**
     * @Store\Property(description="Full exception class name.")
     * @Assert\Type("string")
     * @Assert\NotBlank
     */
    protected $exceptionClass;

    /**
     * @Store\Property(description="Exception stack trace.")
     * @Assert\Type("string")
     */
    protected $stack;

    /**
     * @Store\Property(description="Exception stack trace.")
     * @Assert\Type("string")
     */
    protected $file;

    /**
     * @Store\Property(description="Exception stack trace.")
     * @Assert\Type("string")
     */
    protected $line;

    /**
     * @Store\Property(description="detailled message if any")
     * @Assert\Type("string")
     */
    protected $detailledMessage = "";

    /**
     * @Store\Property(description="messages added to help debug")
     * @Assert\Type("array")
     */
    protected $messages = array();

    public function addMessage($key, $value)
    {
        $this->messages[$key] = $value;
    }

    public function getCompleteMessage()
    {
        return $this->getMessage(). ": " . $this->getDetailledMessage()
               ." in " . $this->getFile() .":" . $this->getLine();
    }
}
