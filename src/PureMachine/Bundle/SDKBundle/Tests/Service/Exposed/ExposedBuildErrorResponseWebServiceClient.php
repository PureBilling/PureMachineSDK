<?php

namespace PureMachine\Bundle\SDKBundle\Tests\Service\Exposed;

use PureMachine\Bundle\SDKBundle\Service\WebServiceClient;


/**
 * @code
 * ./bin/phpunit -v -c app vendor/puremachine/sdk/src/PureMachine/Bundle/SDKBundle/Tests/Service/Exposed/ExposedTranslationWebServiceClient.php
 * @endcode
 */
class ExposedBuildErrorResponseWebServiceClient extends WebServiceClient
{

    public $translator;

    protected function getSymfonyTranslator()
    {
        if(!is_null($this->translator)) return $this->translator;
        return parent::getSymfonyTranslator();
    }

    public function exposedBuildErrorResponse($webServiceName, $version, \Exception $exception, $fullUrl=null, $serialize=false)
    {
        return $this->buildErrorResponse($webServiceName, $version, $exception, $fullUrl, $serialize);
    }

}
