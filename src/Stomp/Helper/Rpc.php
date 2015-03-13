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


class Rpc extends Helper
{
    protected $defaultTimeout = 30;

    /**
     * @param int $defaultTimeout
     */
    public function setDefaultTimeout($defaultTimeout)
    {
        $this->defaultTimeout = $defaultTimeout;
    }

    /**
     * @param $message
     * @param array $headers
     * @param int|null $timeout
     * @return null|string
     */
    public function call($message, array $headers = array(), $timeout = null)
    {
        $sendHeaders = array_merge($this->defaultHeaders, $headers);
        $sendHeaders['reply-to'] = '/temp-queue/' . uniqid('', true);
        $this->client->subscribe($sendHeaders['reply-to'], array(), false);
        $this->client->send($this->destination, $message, $sendHeaders, true);

        if ($timeout === null) {
            $timeout = $this->defaultTimeout;
        }

        $return = null;
        if ($message = $this->client->readMessage($timeout)) {
            $return = $message->getBody();
        }
        $this->client->unsubscribe();
        return $return;
    }
}