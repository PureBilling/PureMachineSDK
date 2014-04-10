<?php
namespace PureMachine\Bundle\SDKBundle\Exception;

use PureMachine\Bundle\SDKBundle\Store\WebService\Response;

class WebServiceException extends Exception
{
    const DEFAULT_ERROR_CODE = 'WS_001';

    const WS_001 = 'WS_001';
    const WS_001_MESSAGE = 'webService error';

    const WS_002 = 'WS_002';
    const WS_002_MESSAGE = 'WebService lookup error';

    const WS_003 = 'WS_003';
    const WS_003_MESSAGE = 'WebService input data validation error';

    const WS_004 = 'WS_004';
    const WS_004_MESSAGE = 'WebService return data validation error';

    const WS_005 = 'WS_005';
    const WS_005_MESSAGE = 'Remote namespace configuration error';

    const WS_006 = 'WS_006';
    const WS_006_MESSAGE = 'webservice annotation error';

    public static function raiseIfError(Response $answer, $displayStack=false)
    {
        if ($answer->getStatus() != 'success') {

            if ($displayStack) {
                $stack = $answer->getAnswer()->getStack();
                foreach($stack as $line) print "$line\n";
            }

            if ($answer instanceof DebugErrorResponse) {
                $message = $answer->getAnswer()->getCompleteMessage();
            } else {
                $message = $answer->getAnswer()->getMessage();
            }

            /**
             * Try to raise with the original exception class
             */
            try {
                $class = $answer->getAnswer()->getExceptionClass();

                if (class_exists($class)) {
                    throw new $class($message, $answer->getAnswer()->getCode());
                }

            } catch (\Exception $e) {}

            $e = new WebServiceException($message, $answer->getAnswer()->getCode());
            $e->getStore()->setMessage($answer->getAnswer()->getMessage());
            $e->getStore()->setDetailledMessage($answer->getAnswer()->getDetailledMessage());
            $e->getStore()->setCode($answer->getAnswer()->getCode());
            throw $e;
        }
    }
}
