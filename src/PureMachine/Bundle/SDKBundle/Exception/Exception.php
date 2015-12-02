<?php
namespace PureMachine\Bundle\SDKBundle\Exception;

use PureMachine\Bundle\SDKBundle\Store\ExceptionStore;

class Exception extends \Exception
{
    private $exceptionStore;

    const DEFAULT_ERROR_CODE = 'GENERIC_001';

    const GENERIC_001 = 'GENERIC_001';
    const GENERIC_001_MESSAGE = 'Unknown error';

    public function __construct($detailledMessage = "", $code = null, \Exception $previous = null,
                                ExceptionStore $exceptionStore=null)
    {
        if ($code == null) $code = static::DEFAULT_ERROR_CODE;

        $message_code = 'static::' . $code . "_MESSAGE";

        if (defined($message_code)) $message = constant($message_code);
        else $message = constant("static::GENERIC_001_MESSAGE");

        if (is_numeric($code)) $numCode = $code;
        else $numCode = 0;

        parent::__construct("$message. $detailledMessage", $numCode, $previous);

        if ($exceptionStore) {
            $this->exceptionStore = $exceptionStore;
        } else {

            $this->exceptionStore = new ExceptionStore();
            $this->exceptionStore->setMessage($message);
            $this->exceptionStore->setDetailledMessage($detailledMessage);
            $this->exceptionStore->setCode($code);
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

    public function getStore()
    {
        return $this->exceptionStore;
    }

    public function setStore($e)
    {
        return $this->exceptionStore = $e;
    }

    public function addMessage($key, $value)
    {
        $this->exceptionStore->addMessage($key, $value);
    }

    public function getErrorCode()
    {
        return $this->exceptionStore->getCode();
    }

    public function setMerchantDetails($merchantMessage)
    {
        $this->addMetadata('merchantDetail', $merchantMessage);
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
     * Build a exceptionStore from a PHP Exception
     * @param \Exception $e
     */
    public static function buildExceptionStore(\Exception $e)
    {
        $exceptionStore = new ExceptionStore();
        $exceptionStore->setMessage($e->getMessage());
        $exceptionStore->setCode($e->getCode());
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
}
