<?php
namespace PureMachine\Bundle\SDKBundle\Exception;

use PureMachine\Bundle\SDKBundle\Store\ExceptionStore;

class Exception extends \Exception
{
    private $exceptionStore;

    const DEFAULT_ERROR_CODE = 'GENERIC_001';

    const GENERIC_001 = 'GENERIC_001';
    const GENERIC_001_MESSAGE = 'Unknown error';

    public function __construct($detailledMessage = "", $code = null, \Exception $previous = NULL)
    {
        if ($code == null) $code = static::DEFAULT_ERROR_CODE;

        $message_code = 'static::' . $code . "_MESSAGE";

        if (defined($message_code)) $message = "$code: " . constant($message_code);
        else $message = "$code: " . constant("static::GENERIC_001_MESSAGE");

        if (is_numeric($code)) $numCode = $code;
        else $numCode = 0;

        parent::__construct("$message. $detailledMessage", $numCode, $previous);

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
            $stackItem = $this->searchForFileAndLineCalledFromStack($stack);
            if (!is_null($stackItem)) {
                $this->exceptionStore->setFile(basename($stackItem['file'],'.php'));
                $this->exceptionStore->setLine($stackItem['line']);
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
    private function searchForFileAndLineCalledFromStack(array $stack)
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

    public function addMessage($key, $value)
    {
        $this->exceptionStore->addMessage($key, $value);
    }

    public function getErrorCode()
    {
        return $this->exceptionStore->getCode();
    }
}
