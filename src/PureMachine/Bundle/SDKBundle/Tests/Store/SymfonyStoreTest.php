<?php

namespace PureMachine\Bundle\SDKBundle\Tests\Store;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

use PureMachine\Bundle\SDKBundle\Tests\Store\StoreClass\DoctrineStoreTest;

/**
 * @code
 * phpunit -v -c app vendor/puremachine/sdk/src/PureMachine/Bundle/SDKBundle/Tests/Store/SymfonyStoreTest.php
 * @endcode
 */
class SymfonyStoreTest extends WebTestCase
{

    /**
     * @code
     * phpunit -v --filter testSymfonyStore -c app vendor/puremachine/sdk/src/PureMachine/Bundle/SDKBundle/Tests/Store/SymfonyStoreTest.php
     * @endcode
     */
    public function testSymfonyStore()
    {
        $store = new DoctrineSToreTest();
        $schema = $store->getJsonSchema();
        $this->assertEquals("BundleName:EntityName", $schema->definition->title->entity);

        /**
         * FIXME: tests are incomplete because we need a database !
         */
        //  $this->assertEquals('???', get_class($store->getTitleEntity()));
    }
}
