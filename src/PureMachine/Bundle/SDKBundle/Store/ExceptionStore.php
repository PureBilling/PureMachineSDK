<?php
namespace PureMachine\Bundle\SDKBundle\Store;

use Symfony\Component\Validator\Constraints as Assert;
use PureMachine\Bundle\SDKBundle\Store\Annotation as Store;
use PureMachine\Bundle\SDKBundle\Exception\ExceptionElementInterface;

/**
 * Class ExceptionStore
 * @package PureMachine\Bundle\SDKBundle\Store
 *
 * @method getErrorMessage()
 * @method getErrorCode()
 * @method getDetailedErrorMessage()
 * @method getDetailedErrorCode()
 * @method getExceptionClass()
 * @method getTicket()
 * @method getDetailledMessage()
 * @method setErrorMessage()
 * @method setErrorCode()
 * @method setDetailedErrorMessage()
 * @method setDetailedErrorCode()
 */
class ExceptionStore extends Base\BaseStore implements ExceptionElementInterface
{
    /**
     * @Store\Property(description="Exception generic message")
     * @Assert\Type("string")
     * @Assert\NotBlank
     */
    protected $errorMessage;

    /**
     * @Store\Property(description="Exception error code.")
     * @Assert\Type("string")
     * @Assert\NotBlank
     */
    protected $errorCode;

    /**
     * @Store\Property(description="Raw exception generic message")
     * @Assert\Type("string")
     * @Assert\NotBlank
     */
    protected $detailedErrorMessage;

    /**
     * @Store\Property(description="Raw exception error code.")
     * @Assert\Type("string")
     * @Assert\NotBlank
     */
    protected $detailedErrorCode;

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
     * @Store\Property(description="Support ticket ID")
     * @Assert\Type("string")
     */
    protected $ticket;

    /**
     * @Store\Property(description="detailed message if any")
     * @Assert\Type("string")
     */
    protected $detailledMessage = "";

    /**
     * @Store\Property(description="internal message if any")
     * @Assert\Type("string")
     */
    protected $internalMessage = "";

    /**
     * @Store\Property(description="messages added to help debug")
     * @Assert\Type("array")
     */
    protected $messages = array();

    /**
     * @Store\Property(description="data manually added")
     * @Assert\Type("array")
     */
    protected $metadata = array();

    public function addMessage($key, $value)
    {
        $this->messages[$key] = $value;
    }

    public function getDetailedMessage()
    {
        return $this->getDetailledMessage();
    }

    public function getCompleteMessage()
    {
        return $this->getErrorMessage(). ": " . $this->getDetailledMessage()
               ." in " . $this->getFile() .":" . $this->getLine();
    }

    public function addMetadata($key, $value)
    {
        $this->metadata[$key] = $value;

        return $this;
    }

    /***
     * FIXME: For retrocompatibility, to remove
     * @return mixed
     */
    public function getMessage()
    {
        return $this->errorMessage;
    }

    /***
     * FIXME: For retrocompatibility, to remove
     * @return mixed
     */
    public function getCode()
    {
        return $this->errorCode;
    }

}
