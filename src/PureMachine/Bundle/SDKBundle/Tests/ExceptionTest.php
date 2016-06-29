<?php

namespace PureMachine\Bundle\SDKBundle\Tests;

use PureMachine\Bundle\SDKBundle\Exception\Exception;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use PureMachine\Bundle\StoreBundle\Store\Base\BaseStoreInterface;
use PureMachine\Bundle\SDKBundle\Exception\ExceptionElementInterface;

/**
 * @code
 * ./bin/phpunit -v -c app vendor/puremachine/sdk/src/PureMachine/Bundle/SDKBundle/Tests/ExceptionTest.php
 * @endcode
 */
class ExceptionTest extends WebTestCase
{

    /**
     * @code
     * ./bin/phpunit -v --filter testMetadataGetterSetterAdder -c app vendor/puremachine/sdk/src/PureMachine/Bundle/SDKBundle/Tests/ExceptionTest.php
     * @endcode
     */
    public function testMetadataGetterSetterAdder()
    {
        $Exception = new Exception();

        // Test setter and getter
        $Exception->setMetadata(array('key' => 'value'));
        $tmpValues = $Exception->getMetadata();
        $this->assertEquals('value', $tmpValues['key']);

        // Test adder and getter
        $Exception->addMetadata('key2', 'value2');
        $tmpValues = $Exception->getMetadata();
        $this->assertEquals('value', $tmpValues['key']);
        $this->assertEquals('value2', $tmpValues['key2']);

        // Test update and getter
        $Exception->addMetadata('key2', 'value3');
        $tmpValues = $Exception->getMetadata();
        $this->assertEquals('value', $tmpValues['key']);
        $this->assertEquals('value3', $tmpValues['key2']);
    }

    /**
     * @code
     * ./bin/phpunit -v --filter testCreateExceptionStoreFromException -c app vendor/puremachine/sdk/src/PureMachine/Bundle/SDKBundle/Tests/ExceptionTest.php
     * @endcode
     */
    public function testCreateExceptionStoreFromException()
    {
        $exception = new Exception();

        $store = $exception->buildExceptionStore(new \Exception("sample simple exception", -1101));

        $this->assertEquals('sample simple exception', $store->getErrorMessage());
        $this->assertEquals(-1101, $store->getErrorCode());
        $this->assertTrue($store instanceof BaseStoreInterface);
        $this->assertTrue($store instanceof ExceptionElementInterface);
    }

}
