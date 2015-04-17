<?php

namespace PureMachine\Bundle\SDKBundle\Tests\Service\Exposed;

use PureMachine\Bundle\SDKBundle\Service\WebServiceClient;


/**
 * @code
 * ./bin/phpunit -v -c app vendor/puremachine/sdk/src/PureMachine/Bundle/SDKBundle/Tests/Service/Exposed/ExposedTranslationWebServiceClient.php
 * @endcode
 */
class ExposedTranslationWebServiceClient extends WebServiceClient
{

    public function exposedTranslation(\Exception $e)
    {
        return $this->translateException($e);
    }

}
