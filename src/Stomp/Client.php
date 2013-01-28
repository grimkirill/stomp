<?php
/**
 * Created by JetBrains PhpStorm.
 * User: kirill
 * Date: 11.01.13
 * Time: 10:48
 * To change this template use File | Settings | File Templates.
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

    protected $_safe = true;

    protected $_queue_prefix = '';

    public function __construct($uri = 'tcp://127.0.0.1:61613', $params = array())
    {
        $this->_connect_options = $params;
        $this->_connect_uri = (array)$uri;

        if (isset($params['queue_prefix'])) {
            $this->_queue_prefix = $params['queue_prefix'];
            unset($params['queue_prefix']);
        }

    }

    public function setSafe($value)
    {
        $this->_safe = $value;
    }

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
            'accept-version' => '1.1',
            'heart-beat'     => '0,0',
        );
        $accepted_headers = array('host', 'login', 'passcode');

        foreach ($accepted_headers AS $header) {
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
            foreach ($headers->getHeaders() AS $key => $value) {
                $message .= ' ' . $key . ' ' . $value . ';';
            }
        }

        if ($frame->getBody()) {
            $message .= ' ' . $frame->getBody();
        }

        throw new StompException($message);
    }


    public function getServerInfo()
    {
        $this->getConnection();
        return $this->_server_info;
    }

    public function getSession()
    {
        $this->getConnection();
        return $this->_server_info['session'];
    }

    public function send($destination, $message, $headers = array())
    {
        $message = strval($message);

        $headers['destination']  = $this->_queue_prefix . $destination;
        $headers['content-type'] = 'text/plain';
        $headers[Frame::CONTENT_LENGTH] = strlen($message);

        $send_frame = new Frame(Frame::COMMAND_SEND, $message, $headers);
        $this->getConnection()->write($send_frame);

        if ($this->_safe && $this->getConnection()->canRead()) {
            $read_frame = $this->getConnection()->read();
            if ($read_frame->getCommand() == Frame::COMMAND_ERROR) {
                $this->_throwStompException($read_frame);
            }
        }
        return true;
    }

    public function transactionBegin($transaction)
    {
        return $this->_transaction(Frame::COMMAND_BEGIN, $transaction);
    }

    public function transactionCommit($transaction)
    {
        return $this->_transaction(Frame::COMMAND_COMMIT, $transaction);
    }

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

    public function subscribe($destination, $headers = array())
    {
        $headers['destination'] = $this->_queue_prefix . $destination;
        $headers['id'] = md5($this->getSession());
        $headers['ack'] = 'client';
        $frame = new Frame(Frame::COMMAND_SUBSCRIBE, '', $headers);
        $this->getConnection()->write($frame);
        /*if ($this->getConnection()->canRead()) {
            $read_frame = $this->getConnection()->read();
            if ($read_frame->getCommand() == Frame::COMMAND_ERROR) {
                $this->_throwStompException($read_frame);
            }
        }*/
        return true;

    }

    /**
     * @return Message
     */
    public function readMessage($timeout = 0)
    {
        if ($frame = $this->getConnection()->readFrame($timeout)) {
            return new Message($this->getConnection(), $frame);
        }
    }

}
