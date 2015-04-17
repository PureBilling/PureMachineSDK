<?php

namespace PureMachine\Bundle\SDKBundle\Tests\Service;

use PureMachine\Bundle\SDKBundle\Tests\Service\Exposed\ExposedBuildErrorResponseWebServiceClient;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @code
 * ./bin/phpunit -v -c app vendor/puremachine/sdk/src/PureMachine/Bundle/SDKBundle/Tests/Service/WebServiceClientTest.php
 * @endcode
 */
class WebServiceClientTest extends WebTestCase
{

    protected function mockTranslator()
    {
        return $this->getMockBuilder('\Symfony\Component\Translation\Translator')
            ->disableOriginalConstructor()
            ->getMock()
            ;
    }

    /**
     * @code
     * ./bin/phpunit -v --filter testBuildErrorResponse -c app vendor/puremachine/sdk/src/PureMachine/Bundle/SDKBundle/Tests/Service/WebServiceClientTest.php
     * @endcode
     */
    public function testBuildErrorResponse()
    {
        $client = static::createClient();
        $container = $client->getContainer();

        $webserviceClient = new ExposedBuildErrorResponseWebServiceClient();
        $webserviceClient->setContainer($container);

        $sampleException = new \Exception("Sample exception in english", -202);
        $responseStore = $webserviceClient->exposedBuildErrorResponse("SampleWebservice", "V1", $sampleException, "http://full-rul", false);

        $this->assertEquals($responseStore->getUrl(), "http://full-rul");
        $this->assertEquals("Sample exception in english", $responseStore->getAnswer()->getMessage());
        $this->assertEquals(-202, $responseStore->getAnswer()->getCode());
    }

    /**
     * @code
     * ./bin/phpunit -v --filter testTranslatedBuildErrorResponse -c app vendor/puremachine/sdk/src/PureMachine/Bundle/SDKBundle/Tests/Service/WebServiceClientTest.php
     * @endcode
     */
    public function testTranslatedBuildErrorResponse()
    {
        $mockedTranslator = $this->mockTranslator();

        $client = static::createClient();
        $container = $client->getContainer();

        $mockedTranslator->method("trans")->willReturn("ejemplo de excepción en español");

        $webserviceClient = new ExposedBuildErrorResponseWebServiceClient();
        $webserviceClient->setContainer($container);
        $webserviceClient->translator = $mockedTranslator;

        $sampleException = new \Exception("Sample exception in english", 1);
        $responseStore = $webserviceClient->exposedBuildErrorResponse("SampleWebservice", "V1", $sampleException, "http://full-rul", false);

        $this->assertEquals($responseStore->getUrl(), "http://full-rul");
        $this->assertEquals("ejemplo de excepción en español", $responseStore->getAnswer()->getMessage());
        $this->assertEquals(1, $responseStore->getAnswer()->getCode());
    }

}
