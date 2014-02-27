<?php

namespace PureMachine\Bundle\SDKBundle\Tests\Store;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use PureMachine\Bundle\SDKBundle\Tests\Store\StoreClass\AliasStore;

/**
 * @code
 * phpunit -v -c app vendor/puremachine/sdk/src/PureMachine/Bundle/SDKBundle/Tests/Store/AliasTest.php
 * @endcode
 */
class AliasTest extends WebTestCase
{

    /**
     * @code
     * phpunit -v --filter testAliases -c app vendor/puremachine/sdk/src/PureMachine/Bundle/SDKBundle/Tests/Store/AliasTest.php
     * @endcode
     */
    public function testAliases()
    {
        /**
         * My old data has a key old_style_title
         *
         * I would like to set it automatically to title property
         */
        $data = array();
        $data['old_style_title'] = 'my title';
        $data['old_description'] = 'my description';
        $store = new AliasStore($data);
        $this->assertEquals($data['old_style_title'], $store->getTitle());
        $this->assertEquals('MY DESCRIPTION', $store->getDescription());

        /**
         * Now, I would like to serialize it, adding also old style name
         */
        $newData = $store->serialize();
        $this->assertEquals($data['old_style_title'], $newData->old_style_title);
        $this->assertEquals('my description', $newData->old_description);
        $this->assertEquals('MY DESCRIPTION', $newData->description);
    }

}
