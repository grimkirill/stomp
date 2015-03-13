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

class Consumer extends Helper
{
    protected $defaultTimeout = 5;

    /**
     * @param int $defaultTimeout
     */
    public function setDefaultTimeout($defaultTimeout)
    {
        $this->defaultTimeout = $defaultTimeout;
    }

    /**
     * @param callable $callback
     * @param callable $condition
     */
    public function run($callback, $condition = null)
    {
        if ($condition === null) {
            $condition = function () {
                return true;
            };
        }
        $this->client->subscribe($this->destination, $this->defaultHeaders);

        while ($condition()) {
            if ($message = $this->client->readMessage($this->defaultTimeout)) {
                if ($result = $callback($message->getBody(), $message->getHeaders())) {
                    $headers = $message->getHeaders();
                    if (array_key_exists('reply-to', $headers)) {
                        $reply = (string) $result;
                        if ($reply) {
                            $this->client->send($headers['reply-to'][0], $reply);
                        }
                    }
                    $message->ack();
                } else {
                    $message->nack();
                }
            }
        }

        $this->client->unsubscribe();
    }
}