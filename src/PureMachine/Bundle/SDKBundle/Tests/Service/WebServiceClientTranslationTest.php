<?php

namespace PureMachine\Bundle\SDKBundle\Tests\Service;

use PureMachine\Bundle\SDKBundle\Tests\Service\Exposed\ExposedTranslationWebServiceClient;

/**
 * @code
 * ./bin/phpunit -v -c app vendor/puremachine/sdk/src/PureMachine/Bundle/SDKBundle/Tests/Service/WebServiceClientTranslationTest.php
 * @endcode
 */
class WebServiceClientTranslationTest extends \PHPUnit_Framework_TestCase
{

    protected function mockContainer()
    {
        return $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->disableOriginalConstructor()
            ->getMock()
            ;
    }

    protected function mockTranslator()
    {
        return $this->getMockBuilder('Symfony\Component\Translation\Translator')
            ->disableOriginalConstructor()
            ->getMock()
            ;
    }

    /**
     * @code
     * ./bin/phpunit -v --filter testTranslationExceptionMethodWithTranslatedCode -c app vendor/puremachine/sdk/src/PureMachine/Bundle/SDKBundle/Tests/Service/WebServiceClientTranslationTest.php
     * @endcode
     */
    public function testTranslationExceptionMethodWithTranslatedCode()
    {
        $mockedContainer = $this->mockContainer();
        $mockedTranslator = $this->mockTranslator();

        $mockedTranslator->expects($this->any())->method("trans")
            ->with(123456)->willReturn("mensaje de ejemplo en castellano");

        $exposedWebServiceClient = new ExposedTranslationWebServiceClient();
        $exposedWebServiceClient->setContainer($mockedContainer);

        $mockedContainer->expects($this->once())->method("get")->with("translator")->willReturn($mockedTranslator);

        $sampleException = new \Exception("sample message in english", 123456);
        $translatedMessage = $exposedWebServiceClient->exposedTranslation($sampleException);

        //Asserting the exception has not changed
        $this->assertEquals($sampleException->getCode(), 123456);
        $this->assertEquals($sampleException->getMessage(), "sample message in english");

        //Asserting the message has been translated
        $this->assertEquals($translatedMessage, "mensaje de ejemplo en castellano");
    }

    /**
     * @code
     * ./bin/phpunit -v --filter testTranslationExceptionMethodWithNotTranslatedCode -c app vendor/puremachine/sdk/src/PureMachine/Bundle/SDKBundle/Tests/Service/WebServiceClientTranslationTest.php
     * @endcode
     */
    public function testTranslationExceptionMethodWithNotTranslatedCode()
    {
        $mockedContainer = $this->mockContainer();
        $mockedTranslator = $this->mockTranslator();

        $mockedTranslator->expects($this->any())->method("trans")
            ->with(123456)->willReturn(123456);

        $exposedWebServiceClient = new ExposedTranslationWebServiceClient();
        $exposedWebServiceClient->setContainer($mockedContainer);

        $mockedContainer->expects($this->once())->method("get")->with("translator")->willReturn($mockedTranslator);

        $sampleException = new \Exception("sample message in english", 123456);
        $translatedMessage = $exposedWebServiceClient->exposedTranslation($sampleException);

        //Asserting the exception has not changed
        $this->assertEquals($sampleException->getCode(), 123456);
        $this->assertEquals($sampleException->getMessage(), "sample message in english");

        //Asserting the message has not been translated
        $this->assertEquals($translatedMessage, null);
    }

}
