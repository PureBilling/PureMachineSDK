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

        parent::__construct("$message. $detailledMessage", 0, $previous);

        $this->exceptionStore = new ExceptionStore();
        $this->exceptionStore->setMessage($message);
        $this->exceptionStore->setDetailledMessage($detailledMessage);
        $this->exceptionStore->setCode($code);
        $this->exceptionStore->setExceptionClass(get_class($this));
        $t = explode('\n', $this->getTraceAsString());
        $this->exceptionStore->setStack($t);

        $stack = $this->getTrace();
        if (count($stack) > 0) {
            $this->exceptionStore->setFile(basename($stack[0]['file'],'.php'));
            $this->exceptionStore->setLine($stack[0]['line']);
        }
    }

    public function getStore()
    {
        return $this->exceptionStore;
    }

    public function addMessage($key, $value)
    {
        $this->exceptionStore->addMessage($key, $value);
    }
}
