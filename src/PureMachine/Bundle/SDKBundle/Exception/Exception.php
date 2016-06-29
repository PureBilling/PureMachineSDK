<?php
namespace PureMachine\Bundle\SDKBundle\Exception;

use PureMachine\Bundle\SDKBundle\Store\ExceptionStore;

class Exception extends \Exception
{

    /**
     * @var ExceptionStore
     */
    private $exceptionStore;

    const DEFAULT_ERROR_CODE = 'GENERIC_001';
    const GENERIC_001 = 'GENERIC_001';
    const GENERIC_001_MESSAGE = 'Unknown error';

    /**
     * Exception constructor.
     *
     * @param string $detailedMessage
     * @param null $code
     * @param \Exception|null $previous
     * @param ExceptionStore|null $exceptionStore
     */
    public function __construct($detailedMessage = "", $code = null, \Exception $previous = null,
                                ExceptionStore $exceptionStore=null)
    {
        if ($code == null) $code = static::DEFAULT_ERROR_CODE;
        $message_code = 'static::' . $code . "_MESSAGE";

        if (defined($message_code)) $message = constant($message_code);
        else $message = constant("static::GENERIC_001_MESSAGE");

        if (is_numeric($code)) $numCode = $code;
        else $numCode = 0;

        parent::__construct($detailedMessage, $numCode, $previous);
        $this->setup($code, $message, null, $detailedMessage, $exceptionStore);
    }

    /**
     * Setups a new internal exception store on the exception class
     * This exception store is built from the arguments of this method call
     *
     * @param $code
     * @param $message
     * @param $merchantMessage
     * @param $debugMessage
     * @param ExceptionStore|null $exceptionStore
     */
    protected function setup($code, $message, $merchantMessage, $debugMessage,
                             ExceptionStore $exceptionStore=null)
    {
        if ($exceptionStore) {
            $this->exceptionStore = $exceptionStore;
        } else {

            $this->exceptionStore = new ExceptionStore();
            $this->exceptionStore->setErrorMessage($message);
            $this->exceptionStore->setDetailledMessage($debugMessage);
            $this->exceptionStore->setErrorCode($code);
            $this->exceptionStore->setExceptionClass(get_class($this));
            $t = explode('\n', $this->getTraceAsString());
            $this->exceptionStore->setStack($t);

            $stack = $this->getTrace();
            if (count($stack) > 0) {
                //Setting default unknown values
                $this->exceptionStore->setFile("unknown");
                $this->exceptionStore->setLine(0);
                //Searching for a valid stackItem
                $stackItem = static::searchForFileAndLineCalledFromStack($stack);
                if (!is_null($stackItem)) {
                    $this->exceptionStore->setFile(basename($stackItem['file'], '.php'));
                    $this->exceptionStore->setLine($stackItem['line']);
                }
            }
        }

        $this->setMerchantDetails($merchantMessage);
    }

    /**
     * Search for a valid stack item with file and line not a raw class
     * call. Return the stack item with this data or null in case not stack item
     * found
     *
     * @param  array      $stack
     * @return array|null
     */
    protected static function searchForFileAndLineCalledFromStack(array $stack)
    {
        for ($i=0;$i<count($stack);$i++) {
            if(array_key_exists("file", $stack[$i]) && array_key_exists("line", $stack[$i])) return $stack[$i];
        }

        return null;
    }

    /**
     * Build a exceptionStore from a PHP Exception
     * @param \Exception $e
     * @return ExceptionStore
     */
    public static function buildExceptionStore(\Exception $e)
    {
        $exceptionStore = new ExceptionStore();
        $exceptionStore->setErrorMessage($e->getMessage());
        $exceptionStore->setErrorCode($e->getCode());
        $exceptionStore->setExceptionClass(get_class($e));
        $t = explode('\n', $e->getTraceAsString());
        $exceptionStore->setStack($t);

        $stack = $e->getTrace();
        if (count($stack) > 0) {
            //Setting default unknown values
            $exceptionStore->setFile("unknown");
            $exceptionStore->setLine(0);
            //Searching for a valid stackItem
            $stackItem = static::searchForFileAndLineCalledFromStack($stack);
            if (!is_null($stackItem)) {
                $exceptionStore->setFile(basename($stackItem['file'],'.php'));
                $exceptionStore->setLine($stackItem['line']);
            }
        }

        return $exceptionStore;
    }

    /*
     * Getters and setters
     */

    /**
     * Returns the exception store
     *
     * @return ExceptionStore
     */
    public function getStore()
    {
        return $this->exceptionStore;
    }

    /**
     * Injects the exception store on the exception
     * instance
     *
     * @param ExceptionStore $e
     * @return Exception
     */
    public function setStore(ExceptionStore $e)
    {
        $this->exceptionStore = $e;
        return $this;
    }

    /**
     * Sets the message on the internal exception store
     *
     * @param $value
     */
    public function setErrorMessage($value)
    {
        $this->exceptionStore->setErrorMessage($value);
    }

    /**
     * Returns the errorCode on the internal exception store
     *
     * @return string
     */
    public function getErrorCode()
    {
        return $this->exceptionStore->getErrorCode();
    }

    /**
     * Concat the sent element message on the errorMessage
     * string.
     *
     * @param $message
     */
    public function addErrorMessage($message)
    {
        if (!$this->exceptionStore->getErrorMessage()) {
            $this->exceptionStore->setErrorMessage('');
        }
        return $this->exceptionStore->setErrorMessage($this->exceptionStore->getErrorMessage().', ' . $message);
    }

    public function setMerchantDetails($merchantMessage)
    {
        $this->addMetadata('merchantDetail', $merchantMessage);
    }

    public function getMerchantDetails()
    {
        $meta = $this->exceptionStore->getMetadata();
        if (array_key_exists('merchantDetail', $meta)) {
            return $meta['merchantDetail'];
        }
        return null;
    }

    public function addMetadata($key, $value)
    {
        $this->exceptionStore->addMetadata($key, $value);
    }

    public function getMetadata()
    {
        return $this->exceptionStore->getMetadata();
    }

    public function setMetadata($value)
    {
        $this->exceptionStore->setMetadata($value);

        return $this;
    }

    /**
     * Returns the detailed error message from the internal exception store
     *
     * @return mixed
     */
    public function getDetailedErrorMessage()
    {
        return $this->exceptionStore->getDetailedErrorMessage();
    }

    /**
     * Returns the detailed error code from the internal exception store
     *
     * @return mixed
     */
    public function getDetailedErrorCode()
    {
        return $this->exceptionStore->getDetailedErrorCode();
    }

    /**
     * Sets the detailed error message of the internal exception store
     *
     * @param $value
     * @return Exception
     */
    public function setDetailedErrorMessage($value)
    {
        $this->exceptionStore->setDetailedErrorMessage($value);
        return $this;
    }

    /**
     * Sets the detailed error code of the internal exception store
     *
     * @param $value
     * @return Exception
     */
    public function setDetailedErrorCode($value)
    {
        $this->exceptionStore->setDetailedErrorCode($value);
        return $this;
    }
}
