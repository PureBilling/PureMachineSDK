<?php

namespace PureMachine\Bundle\SDKBundle\Tests\Store;

use PureMachine\Bundle\SDKBundle\Tests\Store\StoreClass\StoreA;
use PureMachine\Bundle\SDKBundle\Tests\Store\StoreClass\StoreInStore;
use PureMachine\Bundle\SDKBundle\Tests\Store\StoreClass\StoreWithStoreANotBlankIn;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use PureMachine\Bundle\SDKBundle\Tests\Store\StoreClass\StoreDateTime;
use PureMachine\Bundle\SDKBundle\Tests\Store\StoreClass\ComposedStore;
use PureMachine\Bundle\SDKBundle\Tests\Store\StoreClass\PrivateStore;

/**
 * @code
 * phpunit -v -c app vendor/puremachine/sdk/src/PureMachine/Bundle/SDKBundle/Tests/Store/StoreTest.php
 * @endcode
 */
class StoreTest extends WebTestCase
{

    /**
     * @code
     * phpunit -v --filter testAutoSetterGetter -c app vendor/puremachine/sdk/src/PureMachine/Bundle/SDKBundle/Tests/Store/StoreTest.php
     * @endcode
     */
    public function testAutoSetterGetter()
    {
        $store = new StoreClass\TestStore();
        $this->assertEquals('PureMachine\Bundle\SDKBundle\Tests\Store\StoreClass\TestStore', $store->get_className());

        //Try to access to a public property defined a property
        $store->setTestProperty('test');

        $this->assertEquals('test', $store->getTestProperty());

        //Test Manually defined getter
        $store->setPropertyWithCustomGetter('test defined getter');
        $this->assertEquals('getter is forcing the value', $store->getPropertyWithCustomGetter());

        //Test Manually defined setter
        $store->setPropertyWithCustomSetter('test defined setter');
        $this->assertEquals('setter is forcing the value', $store->getPropertyWithCustomSetter());

        //Check default composition Creation
        $this->assertTrue($store->getComposedStore() instanceof ComposedStore);

        //Check if array is defined by default
        $this->assertTrue(is_array($store->getArrayOfComposedStore()));

        //Test get Json defintion
        $schema = $store->getJsonSchema()->definition;

        $this->assertEquals(7, count((array) $schema));
        $this->assertEquals(9, count((array) $schema->testProperty));
        $this->assertEquals('string', $schema->testProperty->type);
        $this->assertEquals('testProperty', $schema->testProperty->description);

        $this->assertEquals('object', $schema->composedStore->type);
        $this->assertEquals('PureMachine\Bundle\SDKBundle\Tests\Store\StoreClass\ComposedStore', $schema->composedStore->storeClasses[0]);

        $this->assertEquals('array', $schema->arrayOfComposedStore->type);
        $this->assertEquals('PureMachine\Bundle\SDKBundle\Tests\Store\StoreClass\ComposedStore', $schema->arrayOfComposedStore->storeClasses[0]);

        //Test array helper functions
        $newStore = new ComposedStore();
        $newStore->setMyProperty('test add function');
        $store->addArrayOfComposedStore($newStore);
        $this->assertEquals(1, count($store->getArrayOfComposedStore()));

        //test with a key
        $store->addArrayOfComposedStore($newStore, 'key');
        $this->assertEquals(2, count($store->getArrayOfComposedStore()));
        $temp = $store->getArrayOfComposedStore();
        $this->assertEquals('test add function', $temp['key']->getMyProperty());
        $this->assertEquals('test add function', $store->getArrayOfComposedStore('key')->getMyProperty());

        //Try to set anither
    }

    public function getSampleData()
    {
        $composed = array();
        $composed['myProperty'] = 'composed Property';

        $arrayOfComposed = array( (object) array('myProperty' => 'AAA'),
                                  (object) array('myProperty' => 'BBB') );

        $data = array();
        $data['testProperty'] = 'test';
        $data['propertyWithCustomGetter'] = 'test defined getter';
        $data['propertyWithCustomSetter'] = 'test defined setter';
        $data['composedStore'] = (object) $composed;
        $data['arrayOfComposedStore'] = $arrayOfComposed;
        $data['simpleArray'] = array('A' => 'a', 'B' => 'b');

        return $data;
    }

    private function checkSerializedObject($obj)
    {
        $this->assertTrue(is_object($obj));

        //Check first level property
        $obj = (array) $obj;
        $this->assertEquals('test', $obj['testProperty']);
        $this->assertEquals('getter is forcing the value', $obj['propertyWithCustomGetter']);
        $this->assertEquals('setter is forcing the value', $obj['propertyWithCustomSetter']);

        //Test composed property
        $composed = $obj['composedStore'];
        $this->assertTrue(is_object($composed));
        $composed = (array) $composed;
        $this->assertEquals('composed Property', $composed['myProperty']);

        //Test array of composed store
        $acomposed = $obj['arrayOfComposedStore'];
        $this->assertTrue(is_array($acomposed));

        $this->assertTrue(is_object($acomposed[0]));
        $acomposed[0] = (array) $acomposed[0];
        $this->assertEquals('AAA', $acomposed[0]['myProperty']);

        $this->assertTrue(is_object($acomposed[1]));
        $acomposed[1] = (array) $acomposed[1];
        $this->assertEquals('BBB', $acomposed[1]['myProperty']);

    }

    /**
     * @code
     * phpunit -v --filter testStoreInitialization -c app vendor/puremachine/sdk/src/PureMachine/Bundle/SDKBundle/Tests/Store/StoreTest.php
     * @endcode
     */
    public function testStoreInitialization()
    {
        //test simple initialization using stdClass
        $init = new \stdClass();
        $init->titleA = 'testA value';
        $store = new StoreClass\StoreA($init);
        $this->assertTrue($store->validate());

        //First level
        $store = new StoreClass\TestStore($this->getSampleData());
        $this->assertEquals('test', $store->getTestProperty());
        $this->assertEquals('getter is forcing the value', $store->getPropertyWithCustomGetter());
        $this->assertEquals('setter is forcing the value', $store->getPropertyWithCustomSetter());

        //Composed Store
        $this->assertTrue($store->getComposedStore() instanceof ComposedStore);
        $this->assertEquals('composed Property', $store->getComposedStore()->getMyProperty());

        //Array of composed Store
        $this->assertEquals(2, count($store->getArrayOfComposedStore()));
        $composedArray = $store->getArrayOfComposedStore();
        $this->assertTrue($composedArray[0] instanceof ComposedStore);
        $this->assertEquals('AAA', $composedArray[0]->getMyProperty());

        $this->assertTrue($composedArray[1] instanceof ComposedStore);
        $this->assertEquals('BBB', $composedArray[1]->getMyProperty());

        //Test simple Store
        $this->assertEquals(2, count($store->getSimpleArray()));
        $sa = $store->getSimpleArray();
        $this->assertEquals('a', $sa['A']);
        $this->assertEquals('b', $sa['B']);

    }

    /**
     * @code
     * phpunit -v --filter testSerializeFunction -c app vendor/puremachine/sdk/src/PureMachine/Bundle/SDKBundle/Tests/Store/StoreTest.php
     * @endcode
     */
    public function testSerializeFunction()
    {
        $data = $this->getSampleData();
        $store = new StoreClass\TestStore($data);

        //Test standard serialization as array
        $obj = $store->serialize();
        $this->checkSerializedObject($obj);

        $obj = (array) $obj;
        //Test is the simpleArray manage array with key/values
        $this->assertTrue(array_key_exists('A', $obj['simpleArray']));
        $this->assertEquals('a', $obj['simpleArray']['A']);
        $this->assertTrue(array_key_exists('B', $obj['simpleArray']));
        $this->assertEquals('b', $obj['simpleArray']['B']);

        //Test standard serialization as StdClass
        $obj = $store->serialize();
        $this->checkSerializedObject($obj, 'is_object');

        //Check if composed Store are serialized to stdClass
        $this->assertEquals('stdClass', get_class($obj->composedStore));

        //Check if composed Array objects has been converted to stdClass
        $this->assertEquals('stdClass', get_class($obj->arrayOfComposedStore[0]));

    }

    /**
     * @code
     * phpunit -v --filter testValidation -c app vendor/puremachine/sdk/src/PureMachine/Bundle/SDKBundle/Tests/Store/StoreTest.php
     * @endcode
     */
    public function testValidation()
    {
        $store = new StoreClass\TestStore();
        $this->assertFalse($store->validate());

        //Store with a property with several store type
        $store = new StoreClass\StoreAB();

        $this->assertNull($store->getStore());
        $this->assertEquals(2, count($store->getJsonSchema()->definition->store->storeClasses));
        $this->assertTrue($store->validate());

        /**
         * Several type allowed for a object
         */

        //Try with StoreA
        $store->setStore(new StoreClass\StoreA());
        $this->assertTrue($store->validate());

        //Try with StoreB
        $store->setStore(new StoreClass\StoreB());
        $this->assertTrue($store->validate());

        //Try with an invalid store
        $store->setStore(new StoreClass\TestStore());
        $this->assertFalse($store->validate());

        /**
         * Several type allowed for an array
         */

        //Store with an array property with several store type
        $store = new StoreClass\StoreABArray();
        $this->assertTrue(is_array($store->getStore()));
        $this->assertEquals(2, count($store->getJsonSchema()->definition->store->storeClasses));
        $this->assertTrue($store->validate());

        //Try with StoreA
        $store->addStore(new StoreClass\StoreA());
        $this->assertTrue($store->validate());

        //Try with StoreB
        $store->addStore(new StoreClass\StoreB());
        $this->assertTrue($store->validate());

        //add an invalid store type into the array.
        $store->addStore(new StoreClass\TestStore());
        $this->assertFalse($store->validate());
    }

    /**
     * @code
     * phpunit -v --filter testPrivateStore -c app vendor/puremachine/sdk/src/PureMachine/Bundle/SDKBundle/Tests/Store/StoreTest.php
     * @endcode
     */
    public function testPrivateStore()
    {
        $privateStore = new PrivateStore();
        $privateStore->setTitleA('A');
        $privateStore->setTitleB('B');

        $json = $privateStore->serialize();
        $this->assertFalse(isset($json->titleB));
    }

    public function fetchNonValidInitializeValues()
    {
        return array(
            array(1),
            array(0.1),
            array("foo"),
            array(true)
        );
    }

    /**
     * @code
     * phpunit -v --filter testDateTimeValueOnStore -c app vendor/puremachine/sdk/src/PureMachine/Bundle/SDKBundle/Tests/Store/StoreTest.php
     * @endcode
     */
    public function testDateTimeValueOnStore()
    {
        $newRef = new \DateTime("now");
        $sampleStore = new StoreDateTime();
        $sampleStore->setValue($newRef);

        //The serialize value should be the unix timestamp
        $serializedStore = $sampleStore->serialize();
        $this->assertTrue(is_int($serializedStore->value));
        $this->assertEquals((int) $newRef->format("U"), $serializedStore->value);

        //Getting the datetime should return a DateTime object
        $fetchedValue = $sampleStore->getValue();
        $this->assertTrue($fetchedValue instanceof \DateTime);
        $this->assertEquals($newRef->format("U"), $fetchedValue->format("U"));

        //Trying to construct a negative timestamp
        try {
            $sampleStore = new StoreDateTime(array(
                "value" => -1,
            ));

            $this->assertTrue(false);
        } catch (\Exception $e) {
            $this->assertTrue(true);
        }

    }

    /**
     * @code
     * phpunit -v --filter testDateTimeSettingAsIntegerValue -c app vendor/puremachine/sdk/src/PureMachine/Bundle/SDKBundle/Tests/Store/StoreTest.php
     * @endcode
     */
    public function testDateTimeSettingAsIntegerValue()
    {
        $newRef = new \DateTime("now");
        $unixTimestamp = $newRef->format("U");
        $sampleStore = new StoreDateTime();
        $sampleStore->setValue($unixTimestamp);

        $fetchedDateTime = $sampleStore->getValue();
        $this->assertTrue($fetchedDateTime instanceof \DateTime);
        $this->assertEquals($unixTimestamp, $fetchedDateTime->format("U"));
    }

    /**
     * @code
     * phpunit -v --filter testArrayAsObject -c app vendor/puremachine/sdk/src/PureMachine/Bundle/SDKBundle/Tests/Store/StoreTest.php
     * @endcode
     */
    public function testArrayAsObject()
    {
        $storeAData = array('titleA' => 'test title');
        /**
         * Classical case
         */
        $data = array();
        $data['storeA'] = (object) $storeAData;
        $data['storesA'] = array();
        $data['storesA'][] = (object) $storeAData;
        $store = new StoreInStore($data);
        $this->assertTrue($store->getStoreA() instanceof StoreA);
        $storesA = $store->getStoresA();
        $this->assertTrue($storesA[0] instanceof StoreA);

        /**
         * object sent as array, not as stdClass
         */
        $data = array();
        $data['storeA'] = array('titleA' => 'test title');
        $data['storesA'] = array();
        $data['storesA'][] = $storeAData;
        $store = new StoreInStore($data);
        $this->assertTrue($store->getStoreA() instanceof StoreA);
        $storesA = $store->getStoresA();
        $this->assertTrue($storesA[0] instanceof StoreA);
    }

    /**
     * @code
     * phpunit -v --filter testStoreWithNullableStore -c app vendor/puremachine/sdk/src/PureMachine/Bundle/SDKBundle/Tests/Store/StoreTest.php
     * @endcode
     */
    public function testStoreWithNullableStore()
    {
        $store = new StoreWithStoreANotBlankIn();
        $store->validate();
        $store->raiseIfInvalid();
    }
}
