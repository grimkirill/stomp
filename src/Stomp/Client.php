<?php

/*
 * This file is part of the Stomp package.
 *
 * (c) Kirill Skatov <kirill@noadmin.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp;

use Stomp\Exception\StompException;
use Stomp\Exception\ConnectionException;

class Client
{
    protected $_connect_options;
    protected $_connect_uri;

    protected $_server_info = array();

    /**
     * @var Connection
     */
    protected $_connection;

    protected $_queue_prefix = '';

    /**
     * @param string|array $uri
     * @param array        $params connections params
     */
    public function __construct($uri = 'tcp://127.0.0.1:61613', $params = array())
    {
        $this->_connect_options = $params;
        $this->_connect_uri = (array) $uri;

        if (isset($params['queue_prefix'])) {
            $this->_queue_prefix = $params['queue_prefix'];
            unset($params['queue_prefix']);
        }
    }

    /**
     * Set prefix for queue name
     * @param $prefix
     */
    public function setQueuePrefix($prefix)
    {
        $this->_queue_prefix = $prefix;
    }

    /**
     * @return Connection
     */
    protected function getConnection()
    {
        if ($this->_connection === null) {
            $this->_connection = new Connection();
            $this->_connection->connect($this->_connect_uri, $this->_connect_options);
            $this->_connect();
        }

        return $this->_connection;
    }

    protected function _connect()
    {
        $connect_params = array(
            'accept-version' => '1.0,1.1,1.2',
            'heart-beat'     => '0,0',
        );
        $accepted_headers = array('host', 'login', 'passcode');

        foreach ($accepted_headers as $header) {
            if (isset($this->_connect_options[$header])) {
                $connect_params[$header] = $this->_connect_options[$header];
            }
        }
        $connect_frame = new Frame(Frame::COMMAND_CONNECT, '', $connect_params);
        $this->_connection->write($connect_frame);

        if ($response = $this->_connection->readFrame()) {
            $this->_server_info = $response->getHeaders();
        } else {
            throw new ConnectionException('No response from server.');
        }
    }

    protected function _throwStompException(Frame $frame)
    {
        $headers = $frame->getHeaders();
        if (isset($headers['message'])) {
            $message = $headers['message'];
        } else {
            $message = $frame->getCommand();
            foreach ($headers->getHeaders() as $key => $value) {
                $message .= ' '.$key.' '.$value.';';
            }
        }

        if ($frame->getBody()) {
            $message .= ' '.$frame->getBody();
        }

        throw new StompException($message);
    }

    /**
     * Return server and connection info
     *
     * @return array
     */
    public function getServerInfo()
    {
        $this->getConnection();

        return $this->_server_info;
    }

    /**
     * Return connection session
     *
     * @return string
     */
    public function getSession()
    {
        $this->getConnection();

        return $this->_server_info['session'];
    }

    /**
     * Send new message
     * @param $destination
     * @param $message
     * @param  array $headers
     * @param  bool  $receipt
     * @return bool
     */
    public function send($destination, $message, $headers = array(), $receipt = false)
    {
        $message = strval($message);

        $headers['destination']  = $this->_queue_prefix.$destination;
        $headers['content-type'] = 'text/plain';
        $headers[Frame::CONTENT_LENGTH] = strlen($message);
        if ($receipt) {
            $headers['receipt'] = md5('receipt-'.$this->getSession());
        }

        $send_frame = new Frame(Frame::COMMAND_SEND, $message, $headers);
        $this->getConnection()->write($send_frame);

        if ($receipt) {
            if ($read_frame = $this->getConnection()->read($receipt)) {
                if ($read_frame->getCommand() == Frame::COMMAND_ERROR) {
                    $this->_throwStompException($read_frame);
                } elseif ($read_frame->getCommand() == Frame::COMMAND_RECEIPT) {
                    $receipt_headers = $read_frame->getHeaders();
                    if ($receipt_headers['receipt-id'] == $headers['receipt']) {
                        return true;
                    }
                }
            }

            return false;
        }

        return true;
    }

    /**
     * @param string $transaction
     * @return bool
     */
    public function transactionBegin($transaction)
    {
        return $this->_transaction(Frame::COMMAND_BEGIN, $transaction);
    }

    /**
     * @param string $transaction
     * @return bool
     */
    public function transactionCommit($transaction)
    {
        return $this->_transaction(Frame::COMMAND_COMMIT, $transaction);
    }

    /**
     * @param string $transaction
     * @return bool
     */
    public function transactionAbort($transaction)
    {
        return $this->_transaction(Frame::COMMAND_ABORT, $transaction);
    }

    protected function _transaction($action, $transaction)
    {
        $frame = new Frame($action, '', array('transaction' => $transaction));
        $this->getConnection()->write($frame);
        if ($this->getConnection()->canRead()) {
            $read_frame = $this->getConnection()->read();
            if ($read_frame->getCommand() == Frame::COMMAND_ERROR) {
                $this->_throwStompException($read_frame);
            }
        }

        return true;
    }

    /**
     * Subscribe
     * @param $destination
     * @param  array $headers
     * @param bool $ack
     * @param null|string $id
     * @throws ConnectionException
     * @return bool
     */
    public function subscribe($destination, $headers = array(), $ack = true, $id = null)
    {
        $defaultHeaders = array(
            'destination' => $this->_queue_prefix . $destination,
            'id' => $id ? strval($id) : md5($this->getSession())
        );
        if ($ack) {
            $defaultHeaders['ack'] = 'client';
        }

        $headers = array_merge($defaultHeaders, $headers);
        $frame = new Frame(Frame::COMMAND_SUBSCRIBE, '', $headers);
        $this->getConnection()->write($frame);

        return true;
    }

    /**
     * Unsubscribe
     * @param null $id
     * @throws ConnectionException
     * @return bool
     */
    public function unsubscribe($id = null)
    {
        $headers['id'] = $id ? strval($id) : md5($this->getSession());
        $frame = new Frame(Frame::COMMAND_UNSUBSCRIBE, '', $headers);
        $this->getConnection()->write($frame);

        return true;
    }

    /**
     * Read next message
     * @return Message
     */
    public function readMessage($timeout = 0)
    {
        if ($frame = $this->getConnection()->readFrame($timeout)) {
            return new Message($this->getConnection(), $frame);
        } else {
            return null;
        }
    }
}
