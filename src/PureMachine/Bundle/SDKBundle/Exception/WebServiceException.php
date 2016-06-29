<?php
namespace PureMachine\Bundle\SDKBundle\Exception;

use PureMachine\Bundle\SDKBundle\Store\ExceptionStore;
use PureMachine\Bundle\SDKBundle\Store\WebService\DebugErrorResponse;
use PureMachine\Bundle\SDKBundle\Store\WebService\ErrorResponse;
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

    public static function raiseIfError($answer, $displayStack=false)
    {
        if ($answer->getStatus() != 'success') {

            if ($answer instanceof ErrorResponse) {

                $message = $answer->getAnswer()->getErrorCode() .": ". $answer->getAnswer()->getErrorMessage() ." \n";

                if ($answer->getAnswer()->isStoreProperty('detailledMessage')) {
                    $message .= $answer->getAnswer()->getDetailledMessage();
                }

                $metadata = $answer->getAnswer()->getMetadata();

                if (array_key_exists('merchantDetail', $metadata)) {
                    $message .= "\nmerchantDetail: " . $metadata['merchantDetail'] . "\n";
                }


                if ($displayStack && ($answer instanceof DebugErrorResponse)) {
                    $stack = $answer->getAnswer()->getStack();
                    foreach($stack as $line) print "$line\n";
                }

            } elseif($answer->isStoreProperty('metadata')) {

                $message  = $answer->getAnswer()->getMessage() . "\n";
                $message .= "detailed: " . $answer->getAnswer()->getDetailedMessage() . "\n";

                $metadata = $answer->getMetadata();

                if (isset($metadata->internalMessage)) {
                    $message .= "internal: " . $metadata->internalMessage . "\n";
                }

                if (isset($metadata->merchantDetail)) {
                    $message .= "merchantDetail: " . $metadata->merchantDetail . "\n";
                }

                if ($answer->isStoreProperty('metadata')) {
                    if ($displayStack && $answer->isStoreProperty('metadata')) {

                        if (isset($metadata->line) && isset($metadata->file)) {
                            print "at " . $metadata->file . ":" . $metadata->line . "\n\n";
                        }


                        if (isset($metadata->stack) && is_array($metadata->stack)) {
                            foreach ($metadata->stack as $line) {
                                print $line . "\n";
                            }
                        }
                    }
                }
            } else {
                $message = $answer->getAnswer()->getMessage();
            }

            /**
             * Try to raise with the original exception class
             */
            $exceptionStore = $answer->getAnswer();
            if ($exceptionStore instanceof ExceptionStore) {
                $class = $answer->getAnswer()->getExceptionClass();
                if (class_exists($class)) {
                    $ex = null;
                    try {
                        $ex = new $class($message, $answer->getAnswer()->getErrorCode(), null);
                        if ($ex instanceof Exception) {
                            $ex->setStore($answer->getAnswer());
                        }

                    } catch (\Exception $e) {}

                    if ($ex instanceof \Exception) {
                        throw $ex;
                    }
                }
            }

            $e = new WebServiceException($message);
            $e->getStore()->setErrorMessage($answer->getAnswer()->getErrorMessage());
            $e->getStore()->setErrorCode($answer->getAnswer()->getErrorCode());
            throw $e;
        }
    }
}
