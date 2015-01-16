<?php
namespace PureMachine\Bundle\SDKBundle\Exception;

class HTTPException extends Exception
{
    const DEFAULT_ERROR_CODE = 'HTTP_001';

    const HTTP_001 = 'HTTP_001';
    const HTTP_001_MESSAGE = 'http error';

    const HTTP_002 = 'HTTP_002';
    const HTTP_002_MESSAGE = 'Connexion refused';

    const HTTP_401 = 'HTTP_401';
    const HTTP_401_MESSAGE = '401: Invalid credentials';

    const HTTP_404 = 'HTTP_404';
    const HTTP_404_MESSAGE = '404: Page not found';

    const HTTP_500 = 'HTTP_500';
    const HTTP_500_MESSAGE = '500: remote server error';
}
