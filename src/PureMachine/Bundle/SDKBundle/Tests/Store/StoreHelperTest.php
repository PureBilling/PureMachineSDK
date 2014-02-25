<?php

namespace PureMachine\Bundle\SDKBundle\Tests\Store;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use PureMachine\Bundle\SDKBundle\Store\Base\StoreHelper;
use Doctrine\Common\Annotations\AnnotationReader;
use PureMachine\Bundle\SDKBundle\Tests\Store\StoreClass\StoreAB;
use PureMachine\Bundle\SDKBundle\Tests\Store\StoreClass\StoreA;
use PureMachine\Bundle\SDKBundle\Tests\Store\StoreClass\StoreB;

/**
 * @code
 * phpunit -v -c app vendor/puremachine/sdk/src/PureMachine/Bundle/SDKBundle/Tests/Store/StoreHelperTest.php
 * @endcode
 */
class StoreHelperTest extends WebTestCase
{

    /**
     * Checks the rebuild of the store by the unserialize method when
     * there are multiple stores as childs (StoreClass1, StoreClass2)
     *
     * @code
     * phpunit -v -c app vendor/puremachine/sdk/src/PureMachine/Bundle/SDKBundle/Tests/Store/StoreHelperTest.php
     * @endcode
     */
    public function testHydrateOnUnserializeChildStores()
    {
        $client = static::createClient();
        $container = $client->getContainer();

        /*
         * Generating input data with StoreA
         */
        $input = (object) [
            'store' => (object) [
                'titleA' => 'fooA',
                '_className' => 'PureMachine\Bundle\SDKBundle\Tests\Store\StoreClass\StoreA',
            ],
            '_className' => 'PureMachine\Bundle\SDKBundle\Tests\Store\StoreClass\StoreAB',
        ];

        $unserializedItem = StoreHelper::unSerialize(
                $input,
                [
                    'PureMachine\Bundle\SDKBundle\Tests\Store\StoreClass\StoreA',
                    'PureMachine\Bundle\SDKBundle\Tests\Store\StoreClass\StoreB',
                ],
                new AnnotationReader(),
                $container
                );

        //Asserting the unserialized structure
        $this->assertTrue($unserializedItem instanceof StoreAB);
        $this->assertTrue($unserializedItem->getStore() instanceof StoreA);
        $this->assertEquals('fooA', $unserializedItem->getStore()->getTitleA());

        /*
         * Generating input data with StoreB
         */
        $input = (object) [
            'store' => (object) [
                'titleB' => 'fooB',
                '_className' => 'PureMachine\Bundle\SDKBundle\Tests\Store\StoreClass\StoreB',
            ],
            '_className' => 'PureMachine\Bundle\SDKBundle\Tests\Store\StoreClass\StoreAB',
        ];

        $unserializedItem = StoreHelper::unSerialize(
                $input,
                [
                    'PureMachine\Bundle\SDKBundle\Tests\Store\StoreClass\StoreA',
                    'PureMachine\Bundle\SDKBundle\Tests\Store\StoreClass\StoreB',
                ],
                new AnnotationReader(),
                $container
                );

        //Asserting the unserialized structure
        $this->assertTrue($unserializedItem instanceof StoreAB);
        $this->assertTrue($unserializedItem->getStore() instanceof StoreB);
        $this->assertEquals('fooB', $unserializedItem->getStore()->getTitleB());
    }

}
