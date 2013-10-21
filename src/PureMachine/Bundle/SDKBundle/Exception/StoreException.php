<?php
namespace PureMachine\Bundle\SDKBundle\Exception;

class StoreException extends Exception
{
    const DEFAULT_ERROR_CODE = 'STORE_001';

    const STORE_001 = 'STORE_001';
    const STORE_001_MESSAGE = 'store error';

    const STORE_002 = 'STORE_002';
    const STORE_002_MESSAGE = 'store validation error';

    const STORE_003 = 'STORE_003';
    const STORE_003_MESSAGE = 'store annotation error';
}
