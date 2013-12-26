<?php

namespace PureMachine\Bundle\SDKBundle\Tests\Store;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

use PureMachine\Bundle\SDKBundle\Tests\Store\StoreClass\DoctrineStoreTest;
use PureMachine\Bundle\SDKBundle\Tests\Store\StoreClass\FakeEntity;

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
        $entity = new FakeEntity();
        $entity->setId('test');
        $store->setId('test');
        $store->setEntity($entity);

        $this->assertEquals("BundleName:EntityName", $schema->definition->id->entity);
        $this->assertEquals('PureMachine\Bundle\SDKBundle\Tests\Store\StoreClass\FakeEntity', get_class($entity));

        //Test set from new entity
        $store->setTitle('test Title');
        $this->assertEquals('test Title', $entity->getTitle());
        $this->assertEquals($store->getTitle(), $entity->getTitle());
        $store->setAutoMapping('test Auto Mapping');
        $this->assertEquals('test Auto Mapping', $entity->getAutoMapping());
        $this->assertEquals($store->getAutoMapping(), $entity->getAutoMapping());
        $store->setsubTitle('test two level mapping');
        $this->assertEquals('test two level mapping', $entity->getSub()->getTitle());
        $this->assertEquals($store->getSubTitle(), $entity->getSub()->getTitle());
        $this->assertTrue($store->validate());

        //Test attaching entity to new store
        $store2 = new DoctrineStoreTest();
        $store2->setId('test');
        $store2->setEntity($entity);

        $this->assertEquals($store->getTitle(), $store2->getTitle());
        $this->assertEquals($store->getAutoMapping(), $store2->getAutoMapping());
        $this->assertEquals($store->getsubTitle(), $store2->getsubTitle());
        $this->assertTrue($store2->validate());

        //change entity value
        $entity->setTitle('new Title');
        $this->assertEquals($entity->getTitle(), $store->getTitle());
        $this->assertEquals($entity->getTitle(), $store2->getTitle());
    }
}
