<?php

/*
 * This file is part of the Stomp package.
 *
 * (c) Kirill Skatov <kirill@noadmin.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace StompTest;

use Stomp\Client;


class FunctionalTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var Client
     */
    protected $client;

    public function setUp()
    {
        $this->client = new Client(array('tcp://127.0.0.1:61613'), array(
            'login' => 'admin',
            'passcode' => 'password',
            'queue_prefix' => '/queue/',
        ));
    }

    public function testConnect()
    {
        $params = $this->client->getServerInfo();
        $this->assertEquals('1.1', $params['version']);

    }
}
