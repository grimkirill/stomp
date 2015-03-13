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

class Producer extends Helper
{
    /**
     * Publish message to queue
     *
     * @param $message
     * @param array $headers
     */
    public function publish($message, array $headers = array())
    {
        $this->client->send($this->destination, (string) $message, array_merge($this->defaultHeaders, $headers));
    }

}