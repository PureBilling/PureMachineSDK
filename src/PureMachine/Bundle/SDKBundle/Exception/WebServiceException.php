<?php
namespace PureMachine\Bundle\SDKBundle\Exception;

use PureMachine\Bundle\SDKBundle\Exception\Exception;
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

            throw new \Exception($answer->getAnswer()->getCompleteMessage());
        }
    }
}
