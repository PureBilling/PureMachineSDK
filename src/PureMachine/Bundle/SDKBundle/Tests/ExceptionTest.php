<?php

namespace PureMachine\Bundle\SDKBundle\Tests;

use PureMachine\Bundle\SDKBundle\Exception\Exception;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @code
     * ./bin/phpunit -v -c app vendor/puremachine/sdk/src/PureMachine/Bundle/SDKBundle/Tests/ExceptionTest.php
 * @endcode
 */
class ExceptionTest extends WebTestCase
{

    /**
     * @code
     * phpunit -v --filter testMetadataGetterSetterAdder -c app vendor/puremachine/sdk/src/PureMachine/Bundle/SDKBundle/Tests/ExceptionTest.php
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

}
