<?php

/*
 * This file is part of the Stomp package.
 *
 * (c) Kirill Skatov <kirill@noadmin.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Helper;


use Stomp\Client;

abstract class Helper
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var string
     */
    protected $destination;

    /**
     * @var array
     */
    protected $defaultHeaders;

    /**
     * @param $client
     * @param $destination
     * @param array $defaultHeaders
     */
    public function __construct($client, $destination, array $defaultHeaders = array())
    {
        $this->client = $client;
        $this->destination = (string) $destination;
        $this->defaultHeaders = (array) $defaultHeaders;
    }
}