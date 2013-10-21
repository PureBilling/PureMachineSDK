<?php
namespace PureMachine\Bundle\SDKBundle\Exception;

class HTTPException extends Exception
{
    const DEFAULT_ERROR_CODE = 'HTTP_001';

    const HTTP_001 = 'HTTP_001';
    const HTTP_001_MESSAGE = 'http error';
}
