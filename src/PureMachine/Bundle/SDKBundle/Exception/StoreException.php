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

    const STORE_004 = 'STORE_004';
    const STORE_004_MESSAGE = 'store entity resolution error';

    const STORE_005 = 'STORE_005';
    const STORE_005_MESSAGE = 'store method resolution error';

    const STORE_006 = 'STORE_006';
    const STORE_006_MESSAGE = 'store not found';

    const STORE_007 = 'STORE_007';
    const STORE_007_MESSAGE = 'key is not a string';
}
